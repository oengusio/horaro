<?php
/*
 * Copyright (c) 2019, Sgt. Kabukiman, https://github.com/sgt-kabukiman
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp;

use horaro\Library\BaseApplication;
use horaro\Library\ObscurityCodec;
use horaro\WebApp\Pager\OffsetLimitPager;
use League\Fractal;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\Translation\Loader\YamlFileLoader;

class Application extends BaseApplication {
	public function __construct(array $values = []) {
		parent::__construct($values);

		$this->setupServices();

		// Connect to DB and fetch runtime configuration, so the routing setup can properly build
		// middleware instances without having to worry whether stuff like the CSRF token name are
		// already known.
		$this['runtime-config']->init();

		// setup error handling
		$this['middleware.errorhandler']->setup($this);

		$this->setupRouting();
	}

	public function setupServices() {
		parent::setupServices();

		$this['user'] = null;

		$this['i18n'] = function() {
			return new I18N($this);
		};

		$this['version'] = function() {
			$filename = HORARO_ROOT.'/version';

			return file_exists($filename) ? trim(file_get_contents($filename)) : 'version N/A';
		};

		$this['csrf'] = function() {
			$factory   = new \RandomLib\Factory();
			$generator = $factory->getMediumStrengthGenerator();
			$name      = $this['config']['csrf_token_name'];

			return new CsrfHandler($name, $generator);
		};

		$this['csp'] = function() {
			return new ContentSecurityPolicy();
		};

		$this['fractal'] = function() {
			$manager = new Fractal\Manager();
			$manager->setSerializer(new Fractal\Serializer\DataArraySerializer());

			if (isset($_GET['embed'])) {
				$manager->parseIncludes($_GET['embed']);
				$manager->setRecursionLimit(2);
			}

			return $manager;
		};

		$this['api.v1.pager'] = $this->factory(function() {
			return new OffsetLimitPager($this['request'], 20, 100);
		});

		$this['resource-resolver'] = function() {
			return new ResourceResolver($this['entitymanager'], $this['obscurity-codec']);
		};

		$this['markdown-converter'] = function() {
			return new Markdown\Converter(new Markdown\MarkdownOnHtml());
		};

		// overwrite transformer to inject the Markdown converter
		$this['schedule-transformer-ical'] = function() {
			$secret = $this['config']['secret'];
			$host   = $this['request']->getHost();

			return new \horaro\Library\ScheduleTransformer\ICalTransformer($secret, $host, $this['obscurity-codec'], $this['markdown-converter']);
		};

		$this->register(new TwigServiceProvider(), array(
			'twig.path' => HORARO_ROOT.'/views',
			'twig.options' => [
				'cache'       => HORARO_ROOT.'/tmp/twig',
				'auto_reload' => $this['config']['debug']
			]
		));

		$this->extend('twig', function($twig, $container) {
			$versions = json_decode(file_get_contents(HORARO_ROOT.'/tmp/assets.json'), true);
			$utils    = new TwigUtils($versions, $this);

			$twig->addGlobal('utils', $utils);

			$twig->addFilter(new \Twig_SimpleFilter('shorten', function($string, $maxlen) use ($utils) {
				return $utils->shorten($string, $maxlen);
			}));

			$twig->addFilter(new \Twig_SimpleFilter('obscurify', function($id, $entityType) use ($utils) {
				return $this['obscurity-codec']->encode($id, $entityType);
			}));

			return $twig;
		});

		// This is only for the views to still be able to access "app.request" instead of
		// "app.requestStack.currentRequest" every time.
		$this['request'] = $this->factory(function() {
			return $this['request_stack']->getCurrentRequest();
		});

		$this['middleware.firewall'] = function() {
			return new Middleware\Firewall($this);
		};

		$this['middleware.resolver'] = function() {
			return new Middleware\Resolver($this['resource-resolver']);
		};

		$this['middleware.errorhandler'] = function() {
			return new Middleware\ErrorHandler($this['sentry-client'], $this['twig'], $this['version']);
		};

		$this['middleware.csrf'] = function() {
			return new Middleware\Csrf($this['csrf']);
		};

		$this['middleware.acl'] = function() {
			return new Middleware\ACL($this['rolemanager']);
		};

		$this['middleware.csp'] = function() {
			return new Middleware\CSP($this['csp']);
		};

		$this['middleware.securityheaders'] = function() {
			$config = $this['config'];
			$maxAge = null;

			if (isset($config['hsts_max_age'])) {
				$maxAge = $config['hsts_max_age'];
			}

			return new Middleware\SecurityHeaders($maxAge);
		};

		$this['controller.index']                  = $this->factory(function() { return new Controller\IndexController($this);                  });
		$this['controller.oauth']                  = $this->factory(function() { return new Controller\OAuthController($this);                  });
		$this['controller.frontend']               = $this->factory(function() { return new Controller\FrontendController($this);               });
		$this['controller.home']                   = $this->factory(function() { return new Controller\HomeController($this);                   });
		$this['controller.event']                  = $this->factory(function() { return new Controller\EventController($this);                  });
		$this['controller.schedule']               = $this->factory(function() { return new Controller\ScheduleController($this);               });
		$this['controller.sitemap']                = $this->factory(function() { return new Controller\SitemapController($this);                });
		$this['controller.schedule.item']          = $this->factory(function() { return new Controller\ScheduleItemController($this);           });
		$this['controller.schedule.column']        = $this->factory(function() { return new Controller\ScheduleColumnController($this);         });
		$this['controller.schedule.import']        = $this->factory(function() { return new Controller\ScheduleImportController($this);         });
		$this['controller.profile']                = $this->factory(function() { return new Controller\ProfileController($this);                });
		$this['controller.admin.index']            = $this->factory(function() { return new Controller\Admin\IndexController($this);            });
		$this['controller.admin.user']             = $this->factory(function() { return new Controller\Admin\UserController($this);             });
		$this['controller.admin.event']            = $this->factory(function() { return new Controller\Admin\EventController($this);            });
		$this['controller.admin.schedule']         = $this->factory(function() { return new Controller\Admin\ScheduleController($this);         });
		$this['controller.admin.utils']            = $this->factory(function() { return new Controller\Admin\Utils\BaseController($this);       });
		$this['controller.admin.utils.config']     = $this->factory(function() { return new Controller\Admin\Utils\ConfigController($this);     });
		$this['controller.admin.utils.tools']      = $this->factory(function() { return new Controller\Admin\Utils\ToolsController($this);      });
		$this['controller.admin.utils.serverinfo'] = $this->factory(function() { return new Controller\Admin\Utils\ServerInfoController($this); });
		$this['controller.api.index']              = $this->factory(function() { return new Controller\Api\IndexController($this);              });
		$this['controller.api.v1.index']           = $this->factory(function() { return new Controller\Api\Version1\IndexController($this);     });
		$this['controller.api.v1.event']           = $this->factory(function() { return new Controller\Api\Version1\EventController($this);     });
		$this['controller.api.v1.schedule']        = $this->factory(function() { return new Controller\Api\Version1\ScheduleController($this);  });

		$this['validator.createaccount'] = function() {
			$userRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\User');

			return new Validator\CreateAccountValidator($userRepo);
		};

		$this['validator.event'] = function() {
			$eventRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\Event');
			$config    = $this['config'];

			return new Validator\EventValidator($eventRepo, array_keys($config['themes']), $config['default_event_theme']);
		};

		$this['validator.login'] = function() {
			$userRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\User');

			return new Validator\LoginValidator($userRepo);
		};

		$this['validator.profile'] = function() {
			$config = $this['config'];

			return new Validator\ProfileValidator(array_keys($config['languages']), $config['default_language']);
		};

		$this['validator.schedule'] = function() {
			$scheduleRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\Schedule');
			$config       = $this['config'];

			return new Validator\ScheduleValidator($scheduleRepo, array_keys($config['themes']));
		};

		$this['validator.schedule.item'] = function() {
			return new Validator\ScheduleItemValidator($this['obscurity-codec']);
		};

		$this['validator.schedule.column'] = function() {
			return new Validator\ScheduleColumnValidator();
		};

		$this['validator.schedule.import'] = function() {
			return new Validator\ScheduleImportValidator();
		};

		$this['validator.admin.user'] = function() {
			$userRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\User');
			$config   = $this['config'];

			return new Validator\Admin\UserValidator($userRepo, $this['rolemanager'], array_keys($config['languages']));
		};

		$this['validator.admin.event'] = function() {
			$eventRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\Event');
			$config    = $this['config'];

			return new Validator\Admin\EventValidator($eventRepo, array_keys($config['themes']), $config['default_event_theme']);
		};

		$this['validator.admin.schedule'] = function() {
			$scheduleRepo = $this['entitymanager']->getRepository('horaro\Library\Entity\Schedule');
			$config       = $this['config'];

			return new Validator\Admin\ScheduleValidator($scheduleRepo, array_keys($config['themes']));
		};

		$this['validator.admin.utils.config'] = function() {
			$config          = $this['config'];
			$languages       = array_keys($config['languages']);
			$defaultLanguage = $config['default_language'];
			$themes          = array_keys($config['themes']);
			$defaultTheme    = $config['default_event_theme'];

			return new Validator\Admin\Utils\ConfigValidator($languages, $defaultLanguage, $themes, $defaultTheme);
		};
	}

    // All implemented routes have been removed :)
	public function setupRouting() {
		$this->before($this['middleware.csrf']);
		$this->before($this['middleware.firewall']);
		$this->before($this['middleware.resolver']);
		$this->before($this['middleware.acl']);
		$this->before('i18n:initLanguage');

		$this->before('middleware.csp:before');
		$this->after('middleware.csp:after');
		$this->after($this['middleware.securityheaders']);

		///////////////////////////////////////////////////////////////////////////////////////////
		// general routes

		///////////////////////////////////////////////////////////////////////////////////////////
		// user backend

		///////////////////////////////////////////////////////////////////////////////////////////
		// admin backend

		///////////////////////////////////////////////////////////////////////////////////////////
		// operator-only extensions to the admin interface

		///////////////////////////////////////////////////////////////////////////////////////////
		// API


		///////////////////////////////////////////////////////////////////////////////////////////
		// generic event/schedule routes

		// We widen the rules for the slugs (".+") to allow for any junk to be appended to the URL.
		// The controller will filter accordingly.
	}

	protected function route($method, $pattern, $endpoint, $requiredRole = null, $noCsrf = false, $json = false) {
		$endpoint   = 'controller.'.$endpoint.'Action';
		$controller = $this->match($pattern, $endpoint)->method($method);
		$route      = $controller->getRoute();

		if ($requiredRole) {
			$route->setDefault(Middleware\Firewall::REQUIRED_ROLE, 'ROLE_'.strtoupper($requiredRole));

			if ($requiredRole === 'admin') {
				$route->setDefault(Middleware\ACL::ADMIN_MODE, true);
			}
		}

		if ($noCsrf) {
			$route->setDefault(Middleware\Csrf::REQUIRE_NO_CSRF_TOKEN, true);
		}

		if ($json) {
			$route->setDefault(Middleware\ErrorHandler::OUTPUT_JSON, true);
		}

		return $controller;
	}
}
