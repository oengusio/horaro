# Bootstrap 3 to Bootstrap 4 Migration Plan

This document details all required HTML/class changes to upgrade from Bootstrap 3 to Bootstrap 4 across every Twig template in the `templates/` directory. Bootstrap 4 is already installed; only markup changes are needed.

**References:**
- [Bootstrap 4.6 Migration Guide](https://getbootstrap.com/docs/4.6/migration/)
- [Twig 3.x Documentation](https://twig.symfony.com/doc/3.x/) (templating language used in this project)
  - [Template Inheritance](https://twig.symfony.com/doc/3.x/tags/extends.html)
  - [Blocks](https://twig.symfony.com/doc/3.x/tags/block.html)
  - [Include](https://twig.symfony.com/doc/3.x/tags/include.html)
  - [Macros](https://twig.symfony.com/doc/3.x/tags/macro.html)
  - [Apply (spaceless)](https://twig.symfony.com/doc/3.x/tags/apply.html)

---

## Twig-Specific Gotchas for This Migration

Since the templates use Twig's inheritance, macros, and includes extensively, these pitfalls must be kept in mind while restructuring the HTML for Bootstrap 4.

### Template Inheritance & Blocks

- **A child block completely replaces the parent.** When you restructure HTML in `layout.twig` (e.g., the navbar), child templates that override `{% block navbar %}` or `{% block navigation %}` will NOT automatically get those changes -- they replace the entire block.
- **`{{ parent() }}` injects the parent's block content.** If you change the navbar wrapper HTML in `layout.twig`, all children using `{{ parent() }}` in that block will inherit the structural changes. This can break layouts if the child wraps `parent()` output in incompatible markup.
- **Audit every child that uses `parent()`.** Ensure their wrapper markup remains compatible with the new parent structure.
- **A block inside an `{% if %}` is NOT conditional** -- it always defines an overridable block. Put conditionals inside the block, not around it.

### Included Templates

- **Changes to included partials propagate everywhere.** `navigation.twig` is included in `layout.twig` -- changing its structure (e.g., adding `.nav-item`/`.nav-link`) affects every page. This is desirable here but requires testing all pages.
- **Variable flow is one-way in.** Included templates receive the caller's context but cannot pass variables back. This doesn't affect class changes but is relevant if restructuring requires new conditional logic.
- **`{% include 'x' only %}` strips all context.** If any include uses `only`, verify the partial still has access to needed variables after restructuring.

### Whitespace and `{% apply spaceless %}`

- **`spaceless` only removes whitespace between HTML tags** (between `>` and `<`). It does NOT affect whitespace inside tags, so `class="foo  bar"` is safe. However, adding/removing wrapper elements (e.g., removing `.navbar-header` div) can cause previously separate tags to collapse.
- **`spaceless` is deprecated as of Twig 3.12.** Consider replacing with manual whitespace control (`{%-`/`-%}`) during this migration. The `calendar.twig` and `frontend/event/event.twig` templates use `{% apply spaceless %}` blocks that may need attention.
- **Trim tags (`{%-`/`-%}`) remove ALL whitespace including newlines.** Use carefully when restructuring to avoid collapsing indentation into adjacent elements.

### Macros (`macros.twig`)

- **Macros have NO access to parent template variables.** They only see their own arguments. The `form_errors` and `back_button` macros in `macros.twig` are self-contained, so changing class names inside them is safe and will propagate to all call sites.
- **Macro argument defaults:** As of Twig 3.29, calling a macro without a value for an argument with no default is deprecated. When changing macro signatures, add new parameters at the end with explicit defaults.
- **`_self` import pattern** (`{% import _self as m %}`) is fully supported in Twig 3.x. No changes needed.

### Knockout.js Data-Bind Attributes

- **Several templates build CSS classes inside `data-bind` attributes** (e.g., `schedule/item.twig`, `schedule/column.twig`). Twig processes server-side first, then Knockout runs client-side. There is no delimiter conflict, but **Twig will HTML-escape quotes** inside attribute values. When changing class names inside `data-bind="attr: { class: '...' }"`, avoid introducing unescaped quotes that break the attribute.
- **String concatenation in Knockout bindings** (e.g., `'btn btn-xs' + (condition && ' disabled' || ' btn-default')`) must be updated to the new class names (`btn-sm`, `btn-secondary`) without breaking the JS expression syntax.

### Conditional Class Building

- **Pattern: `class="form-group{{ utils.formClass(form, 'name') }}"`** -- the helper returns a string like ` has-error`. In BS4, validation states use different mechanisms (`:invalid` pseudo-class or `.is-invalid`). Ensure the `utils.formClass()` helper is updated to return BS4-compatible classes, or replace with Symfony form error rendering.
- **The `~` (concatenation) operator has lower precedence than `|` (filter).** `"btn " ~ var|lower` applies `lower` only to `var`. Use parentheses if combining expressions with filters.

---

## Twig Utility Classes (src/Twig/)

The project has two PHP files under `src/Twig/` that output Bootstrap-specific CSS classes directly in PHP. These **must** be updated alongside the templates, or the migrated markup will still contain Bootstrap 3 classes.

**Docs:** [Twig Extensions](https://twig.symfony.com/doc/3.x/advanced.html#creating-an-extension)

### src/Twig/TwigUtils.php

| Line | Method | Current Output | Required Change |
|------|--------|----------------|-----------------|
| 105-108 | `formClass()` | Returns `' has-error'` when a field is invalid | Replace with `' is-invalid'` (Bootstrap 4 uses `.is-invalid` class on the input itself or parent). **Note:** BS4 validation is typically applied directly to `.form-control` elements, not the `.form-group` wrapper. Consider whether this helper should instead add the class to the input rather than the form-group div. See [BS4 Validation](https://getbootstrap.com/docs/4.6/components/forms/#validation). |
| 124-134 | `roleClass()` | Returns `'default'` for `ROLE_GHOST` | Replace with `'secondary'` (`text-default` does not exist in BS4; the equivalent context class is `secondary`). |
| 148-156 | `roleBadge()` | Outputs `<span class="label h-role h-role-%s label-%s">` | Replace `label` with `badge` and `label-%s` with `badge-%s`. Result: `<span class="badge h-role h-role-%s badge-%s">`. Also note that `label-default` should become `badge-secondary`. |

### src/Twig/HoraroExtension.php

No Bootstrap classes are output by this file. It only provides `obscurify` and `shorten` filters. **No changes needed.**

### Impact on Templates

- **`utils.formClass(form, 'field')`** is called in nearly every form template (event/form, schedule/form, profile/form, register, admin forms). The returned class is appended to the `form-group` div. In Bootstrap 4, validation feedback uses `.is-invalid` on the form control and `.invalid-feedback` for error messages. Two options:
  1. **Minimal change:** Keep appending to the wrapper div but return `' was-validated'` or a custom class, and ensure CSS targets accordingly.
  2. **Proper BS4 approach:** Refactor to add `.is-invalid` to the `<input>` element directly and use `.invalid-feedback` for error messages. This is a larger change that affects all form templates.

- **`utils.roleBadge()`** is called in `admin/users/index.twig` (line 49). The output uses the BS3 `.label` component which is renamed to `.badge` in BS4.

- **`utils.roleClass()`** is called in `macros.twig` (lines 52, 54) via `text-{{ utils.roleClass(user.role) }}`. Since it can return `'default'`, this produces `text-default` which does not exist in BS4. It must return `'secondary'` instead.

---

## Table of Contents

1. [templates/layout.twig](#templateslayouttwig)
2. [templates/backend.twig](#templatesbackendtwig)
3. [templates/frontend.twig](#templatesfrontendtwig)
4. [templates/navigation.twig](#templatesnavigationtwig)
5. [templates/macros.twig](#templatesmacrostwig)
6. [templates/home/home.twig](#templateshomehometwig)
7. [templates/index/login.twig](#templatesindexlogintwig)
8. [templates/index/register.twig](#templatesindexregistertwig)
9. [templates/index/welcome.twig](#templatesindexwelcometwig)
10. [templates/index/api.twig](#templatesindexapitwig)
11. [templates/index/calendar.twig](#templatesindexcalendartwig)
12. [templates/index/contact.twig](#templatesindexcontacttwig)
13. [templates/index/licenses.twig](#templatesindexlicensestwig)
14. [templates/event/detail.twig](#templateseventdetailtwig)
15. [templates/event/form.twig](#templateseventformtwig)
16. [templates/event/confirmation.twig](#templateseventconfirmationtwig)
17. [templates/profile/form.twig](#templatesprofileformtwig)
18. [templates/profile/oauth.twig](#templatesprofileoauthtwig)
19. [templates/schedule/detail.twig](#templatesscheduledetailtwig)
20. [templates/schedule/form.twig](#templatesscheduleformtwig)
21. [templates/schedule/confirmation.twig](#templatesscheduleconfirmationtwig)
22. [templates/schedule/columns.twig](#templatesschedulecolumnstwig)
23. [templates/schedule/column.twig](#templatesschedulecolumntwig)
24. [templates/schedule/item.twig](#templatesscheduleitemtwig)
25. [templates/schedule/import.twig](#templatesscheduleimporttwig)
26. [templates/schedule/import-result.twig](#templatesscheduleimport-resulttwig)
27. [templates/frontend/layout.twig](#templatesfrontendlayouttwig)
28. [templates/frontend/event/event.twig](#templatesfrontendeventeventtwig)
29. [templates/frontend/schedule/schedule.twig](#templatesfrontendschedulescheduletwig)
30. [templates/frontend/schedule/ical.twig](#templatesfrontendscheduleicaltwig)
31. [templates/frontend/schedule/not_found.twig](#templatesfrontendschedulenot_foundtwig)
32. [templates/bundles/TwigBundle/Exception/error.html.twig](#templatesbundlestwigbundleexceptionerrorhtmltwig)
33. [templates/bundles/TwigBundle/Exception/error403.html.twig](#templatesbundlestwigbundleexceptionerror403htmltwig)
34. [templates/bundles/TwigBundle/Exception/error404.html.twig](#templatesbundlestwigbundleexceptionerror404htmltwig)
35. [templates/admin/layout.twig](#templatesadminlayouttwig)
36. [templates/admin/dashboard.twig](#templatesadmindashboardtwig)
37. [templates/admin/users/index.twig](#templatesadminusersindextwig)
38. [templates/admin/users/view.twig](#templatesadminusersviewtwig)
39. [templates/admin/users/form.twig](#templatesadminusersformtwig)
40. [templates/admin/events/index.twig](#templatesadmineventsindextwig)
41. [templates/admin/events/view.twig](#templatesadmineventsviewtwig)
42. [templates/admin/events/form.twig](#templatesadmineventsformtwig)
43. [templates/admin/events/confirmation.twig](#templatesadmineventsconfirmationtwig)
44. [templates/admin/schedules/index.twig](#templatesadminschedulesindextwig)
45. [templates/admin/schedules/view.twig](#templatesadminschedulesviewtwig)
46. [templates/admin/schedules/form.twig](#templatesadminschedulesformtwig)
47. [templates/admin/schedules/confirmation.twig](#templatesadminschedulesconfirmationtwig)
48. [templates/admin/utils/layout.twig](#templatesadminutilslayouttwig)
49. [templates/admin/utils/config.twig](#templatesadminutilsconfigtwig)
50. [templates/admin/utils/tools.twig](#templatesadminutilstoolstwig)
51. [templates/admin/utils/serverinfo.twig](#templatesadminutilsserverinfotwig)

---

## templates/layout.twig

This is the base layout template. It contains the main navbar and footer grid.

**Docs:** [Navbar](https://getbootstrap.com/docs/4.6/components/navbar/), [Grid](https://getbootstrap.com/docs/4.6/layout/grid/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 34 | `navbar-default navbar-static-top` | Replace with `navbar-light bg-light navbar-expand-md` (or `-lg`). Remove `navbar-static-top` (no longer exists; use `.sticky-top` if sticky behavior is needed). |
| 37 | `<div class="navbar-header">` | Remove this wrapper div entirely. In BS4 the brand and toggler sit directly inside the container. |
| 38 | `<button class="navbar-toggle"` with three `<span class="icon-bar">` children | Replace with `<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#h-collapse-nav" aria-controls="h-collapse-nav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>` |
| 39 | `<span class="sr-only">Toggle navigation</span>` | Remove (covered by `aria-label` on button). |
| 40-42 | Three `<span class="icon-bar"></span>` | Remove (replaced by `.navbar-toggler-icon`). |
| 61 | `col-xs-8` | Replace with `col-8` (no `xs` infix in BS4). |
| 67 | `col-xs-4` | Replace with `col-4`. |
| 67 | `text-right` | Replace with `text-right` (still works) or use `ml-auto` for flexbox alignment. |

---

## templates/backend.twig

Extends `layout.twig`. Contains asset references only.

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 8 | `bootstrap3-editable` CSS reference | Update to a Bootstrap 4 compatible version of x-editable or remove if already handled. This is an asset/dependency change, not a class change. |

No Bootstrap class changes are needed in this file itself -- it only loads CSS/JS.

---

## templates/frontend.twig

Extends `layout.twig`. Contains asset references only.

### Changes Required

No Bootstrap class changes needed. This file only loads stylesheets and scripts.

---

## templates/navigation.twig

This file contains the main navigation bar content (login form, nav items, dropdowns).

**Docs:** [Navbar](https://getbootstrap.com/docs/4.6/components/navbar/), [Dropdowns](https://getbootstrap.com/docs/4.6/components/dropdowns/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 5 | `class="navbar-form navbar-right"` | Replace with `class="form-inline ml-auto"` (`navbar-form` is dropped; use `form-inline` + flex utility). |
| 17 | `<ul class="nav navbar-nav navbar-right">` | Replace with `<ul class="navbar-nav ml-auto">`. |
| 24 | `<ul class="nav navbar-nav">` | Replace with `<ul class="navbar-nav mr-auto">`. |
| 24-32 | `<li><a href="...">` nav items | Add `class="nav-item"` to each `<li>` and `class="nav-link"` to each `<a>`. |
| 34 | `<ul class="nav navbar-nav navbar-right">` | Replace with `<ul class="navbar-nav ml-auto">`. |
| 34-44 | All `<li>` items | Add `class="nav-item"` to `<li>`, `class="nav-link"` to `<a>`. |
| 42 | `<span class="hidden-lg hidden-md hidden-sm">Regular Backend</span>` | Replace with `<span class="d-lg-none d-md-none d-sm-none d-inline">Regular Backend</span>` or simply `<span class="d-block d-sm-none">Regular Backend</span>`. |
| 46 | `<ul class="nav navbar-nav">` | Replace with `<ul class="navbar-nav mr-auto">`. |
| 46-75 | All `<li><a>` items | Add `.nav-item` to `<li>`, `.nav-link` to `<a>`. |
| 48 | `<li class="dropdown">` | Change to `<li class="nav-item dropdown">`. |
| 49 | `<a href="#" class="dropdown-toggle" data-toggle="dropdown">...<span class="caret"></span></a>` | Remove `<span class="caret"></span>` (auto-generated in BS4). Add `class="nav-link dropdown-toggle"`. |
| 51-58 | `<li><a href="...">` inside dropdown menu | Add `class="dropdown-item"` to each `<a>`. Remove wrapping `<li>` elements (use `<a class="dropdown-item">` directly, or keep `<li>` but still add `.dropdown-item` to `<a>`). |
| 56 | `<li class="divider"></li>` | Replace with `<div class="dropdown-divider"></div>`. |
| 61 | Same dropdown pattern for "My Schedules" | Same changes as above (remove caret, add nav-link/dropdown-toggle, dropdown-item on children, dropdown-divider). |
| 70 | `<li class="divider"></li>` | Replace with `<div class="dropdown-divider"></div>`. |
| 77 | `<ul class="nav navbar-nav navbar-right">` | Replace with `<ul class="navbar-nav ml-auto">`. |
| 77-98 | All `<li><a>` items | Add `.nav-item`/`.nav-link`. |
| 95 | `<span class="hidden-lg hidden-md hidden-sm">Admin Dashboard</span>` | Replace with `<span class="d-block d-sm-none">Admin Dashboard</span>`. |

---

## templates/macros.twig

Contains reusable Twig macros used across the project.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Buttons](https://getbootstrap.com/docs/4.6/components/buttons/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 4 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">` or `<span class="form-text text-muted">`. |
| 43 | `btn btn-default btn-sm` (back_button macro) | Replace `btn-default` with `btn-secondary`. |

---

## templates/home/home.twig

Home page for logged-in users showing events as tiles.

**Docs:** [Cards](https://getbootstrap.com/docs/4.6/components/card/) (replaces wells), [Breadcrumb](https://getbootstrap.com/docs/4.6/components/breadcrumb/), [Grid](https://getbootstrap.com/docs/4.6/layout/grid/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 6-8 | `<ul class="breadcrumb"><li>Home</li></ul>` | Wrap in `<nav aria-label="breadcrumb">`, change to `<ol class="breadcrumb">`, add `class="breadcrumb-item active"` to `<li>`. |
| 18 | `col-xs-12` | Replace with `col-12`. |
| 19 | `<div class="well well-sm">` | Replace with `<div class="card card-body">` (wells are removed in BS4). |
| 31 | `col-xs-12` | Replace with `col-12`. |
| 32 | `<div class="well well-sm h-adder">` | Replace with `<div class="card card-body h-adder">`. |

---

## templates/index/login.twig

Login page with horizontal form.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Cards](https://getbootstrap.com/docs/4.6/components/card/), [Grid](https://getbootstrap.com/docs/4.6/layout/grid/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 8 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 13 | `class="form-horizontal"` | Remove `form-horizontal` (deprecated). Add `row` to each `.form-group` div. |
| 18 | `<div class="form-group">` | Change to `<div class="form-group row">`. |
| 19 | `<label class="col-lg-3 control-label"` | Replace `control-label` with `col-form-label`. |
| 25 | `<div class="form-group">` | Change to `<div class="form-group row">`. |
| 26 | `control-label` | Replace with `col-form-label`. |
| 36 | `<div class="form-group">` | Change to `<div class="form-group row">`. |
| 55 | `col-xs-8 col-xs-offset-2` | Replace with `col-8 offset-2` (no `xs` infix; `offset-*` replaces `col-*-offset-*`). |
| 55 | All `col-*-offset-*` classes | Replace with `offset-*-*` (e.g., `col-lg-offset-3` becomes `offset-lg-3`). |
| 60 | `col-xs-12` | Replace with `col-12`. |
| 66 | `col-xs-12` | Replace with `col-12`. |

---

## templates/index/register.twig

Registration form page.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Cards](https://getbootstrap.com/docs/4.6/components/card/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 15 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 16 | `class="form-horizontal"` | Remove. Add `row` to each `.form-group`. |
| 20-21 | `<div class="form-group..."><label class="col-lg-3 control-label"` | Add `row` to form-group, replace `control-label` with `col-form-label`. |
| 33 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 38-39 | Same pattern for password group | Same changes. |
| 51 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 57-58 | Same pattern for password2 group | Same changes. |
| 69 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 74-75 | Same pattern for display_name group | Same changes. |
| 85-86 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 106 | `<span class="hidden-xs">Side</span><span class="hidden-lg hidden-md hidden-sm">Foot</span>` | Replace with `<span class="d-none d-sm-inline">Side</span><span class="d-sm-none">Foot</span>`. |
| 106 | All `col-xs-12` | Replace with `col-12`. |

---

## templates/index/welcome.twig

Welcome/landing page.

**Docs:** [Jumbotron](https://getbootstrap.com/docs/4.6/components/jumbotron/), [Buttons](https://getbootstrap.com/docs/4.6/components/buttons/), [Grid](https://getbootstrap.com/docs/4.6/layout/grid/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 54 | `btn btn-sm btn-default` | Replace `btn-default` with `btn-secondary`. |
| 63 | `<div class="jumbotron">` | Still works in BS4 (jumbotron exists in 4.6). No change needed. |
| 144-191 | All `col-xs-6` | Replace with `col-6`. |

---

## templates/index/api.twig

API documentation page. Only uses basic table/alert classes.

**Docs:** [Tables](https://getbootstrap.com/docs/4.6/content/tables/)

### Changes Required

No changes needed. The file uses `.table`, `.table-striped`, `.alert`, `.alert-info` and grid classes -- all of which are unchanged in BS4 (no `col-xs-*` is used either). This file is already compatible.

---

## templates/index/calendar.twig

Calendar view page.

**Docs:** [Grid](https://getbootstrap.com/docs/4.6/layout/grid/), [Utilities](https://getbootstrap.com/docs/4.6/utilities/display/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 16 | `btn-xs` | Replace with `btn-sm` (`btn-xs` is removed in BS4). |
| 18-19 | `btn-xs` | Replace with `btn-sm`. |
| 28-29 | `btn-xs` | Replace with `btn-sm`. |
| 36-39 | `btn-xs` | Replace with `btn-sm`. |
| 50-52 | `btn-xs` | Replace with `btn-sm`. |
| 58 | `<div class="hidden-xs">` | Replace with `<div class="d-none d-sm-block">`. |
| 98 | `<div class="visible-xs">` | Replace with `<div class="d-block d-sm-none">`. |

---

## templates/index/contact.twig

Contact page.

**Docs:** [List Group](https://getbootstrap.com/docs/4.6/components/list-group/), [Grid](https://getbootstrap.com/docs/4.6/layout/grid/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 23 | `col-xs-12` | Replace with `col-12`. |
| 32 | `col-xs-12` | Replace with `col-12`. |

The `.list-group-item-heading` and `.list-group-item-text` classes still work in BS4 (they map to sizing utilities but remain functional).

---

## templates/index/licenses.twig

Licenses/acknowledgments page. Only uses basic grid and no BS3-specific components.

### Changes Required

No changes needed. This file only uses `.row`, `.col-lg-12`, `.col-md-12` and raw HTML headings. All compatible with BS4.

---

## templates/event/detail.twig

Event detail page in the backend (shows schedules as tiles, event info in a well).

**Docs:** [Cards](https://getbootstrap.com/docs/4.6/components/card/), [Breadcrumb](https://getbootstrap.com/docs/4.6/components/breadcrumb/), [Utilities](https://getbootstrap.com/docs/4.6/utilities/float/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 6-9 | `<ul class="breadcrumb h-jail"><li><a>...</a></li><li>...</li></ul>` | Wrap in `<nav aria-label="breadcrumb">`, change to `<ol class="breadcrumb h-jail">`, add `class="breadcrumb-item"` to each `<li>`, add `active` class to last item. |
| 21 | `col-xs-12` | Replace with `col-12`. |
| 22-23 | `<div class="well well-sm">` | Replace with `<div class="card card-body p-2">` (or use custom sizing). |
| 32 | `col-xs-12` | Replace with `col-12`. |
| 33 | `<div class="well well-sm h-adder">` | Replace with `<div class="card card-body p-2 h-adder">`. |
| 52 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 54 | `pull-right` | Replace with `float-right`. |

---

## templates/event/form.twig

Event create/edit form.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Input Group](https://getbootstrap.com/docs/4.6/components/input-group/), [Cards](https://getbootstrap.com/docs/4.6/components/card/), [Breadcrumb](https://getbootstrap.com/docs/4.6/components/breadcrumb/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 7-15 | `<ul class="breadcrumb h-jail">` with bare `<li>` elements | Wrap in `<nav>`, use `<ol>`, add `.breadcrumb-item` to each `<li>`, `.active` to last. |
| 25 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 35 | `class="form-horizontal"` | Remove. Add `row` to each `.form-group`. |
| 45 | `<label class="col-lg-3 control-label"` | Replace `control-label` with `col-form-label`. |
| 45 | `<div class="form-group...">` | Add `row` class. |
| 62-63 | Same pattern | Same changes (all form-groups get `row`, all `control-label` become `col-form-label`). |
| 79, 95, 112, 130-131, 148, 160, 176 | Same form-group pattern | Same changes. |
| 98 | `<div class="input-group-addon">https://twitch.tv/</div>` | Replace with `<div class="input-group-prepend"><span class="input-group-text">https://twitch.tv/</span></div>`. |
| 116 | `<div class="input-group-addon">@</div>` | Replace with `<div class="input-group-prepend"><span class="input-group-text">@</span></div>`. |
| 136 | `<div class="input-group-addon">@</div>` | Same as above. |
| 195 | `<span class="hidden-xs">Side</span><span class="hidden-lg hidden-md hidden-sm">Foot</span>` | Replace with `<span class="d-none d-sm-inline">Side</span><span class="d-sm-none">Foot</span>`. |
| 232 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 244 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 254 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 261 | `<div class="panel panel-default remarkable-preview-panel">` | Replace with `<div class="card remarkable-preview-panel">`. |
| 262 | `<div class="panel-body remarkable-preview"` | Replace with `<div class="card-body remarkable-preview"`. |
| 292 | `col-xs-4` | Replace with `col-4`. |

---

## templates/event/confirmation.twig

Event deletion confirmation page.

**Docs:** [Breadcrumb](https://getbootstrap.com/docs/4.6/components/breadcrumb/), [Buttons](https://getbootstrap.com/docs/4.6/components/buttons/), [Grid](https://getbootstrap.com/docs/4.6/layout/grid/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 7-11 | `<ul class="breadcrumb h-jail">` | Wrap in `<nav>`, use `<ol>`, add `.breadcrumb-item` to each `<li>`. |
| 22 | `col-lg-offset-3`, `col-md-offset-3`, `col-sm-offset-2` | Replace with `offset-lg-3`, `offset-md-3`, `offset-sm-2`. |
| 22 | `col-xs-6` | Replace with `col-6`. |
| 23 | `btn btn-default btn-sm` | Replace `btn-default` with `btn-secondary`. |
| 26 | `col-xs-6` | Replace with `col-6`. |

---

## templates/profile/form.twig

User profile edit form with password change.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Cards](https://getbootstrap.com/docs/4.6/components/card/), [Breadcrumb](https://getbootstrap.com/docs/4.6/components/breadcrumb/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 7-10 | `<ul class="breadcrumb">` | Wrap in `<nav>`, use `<ol>`, add `.breadcrumb-item` to each `<li>`. |
| 20 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 21 | `class="form-horizontal"` | Remove. Add `row` to each `.form-group`. |
| 28 | `<label class="col-lg-4 control-label"` | Replace `control-label` with `col-form-label`. |
| 28 | `<div class="form-group">` | Add `row`. |
| 39 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 44-45 | form-group + control-label | Add `row`, replace `control-label` with `col-form-label`. |
| 57-58 | Same pattern | Same changes. |
| 67 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 72-73 | form-group | Add `row`. |
| 86 | `<span class="hidden-xs">Side</span><span class="hidden-lg hidden-md hidden-sm">Foot</span>` | Replace with `<span class="d-none d-sm-inline">Side</span><span class="d-sm-none">Foot</span>`. |
| 99 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 104 | `class="form-horizontal"` | Remove. Add `row` to each `.form-group`. |
| 110-111 | form-group + control-label | Add `row`, replace `control-label` with `col-form-label`. |
| 118-119 | Same | Same. |
| 126-127 | Same | Same. |

---

## templates/profile/oauth.twig

OAuth/Twitch account management page.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Cards](https://getbootstrap.com/docs/4.6/components/card/), [Breadcrumb](https://getbootstrap.com/docs/4.6/components/breadcrumb/), [Utilities](https://getbootstrap.com/docs/4.6/utilities/display/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 7-11 | `<ul class="breadcrumb">` | Wrap in `<nav>`, use `<ol>`, add `.breadcrumb-item` to each `<li>`. |
| 20 | `col-xs-12` | Replace with `col-12`. |
| 33 | `class="form-horizontal h-confirmation"` | Remove `form-horizontal`. |
| 55 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 56 | `class="form-horizontal h-confirmation"` | Remove `form-horizontal`. |
| 61 | `col-xs-12` twice | Replace with `col-12`. |
| 65 | `hidden-sm hidden-xs` | Replace with `d-none d-md-block` (hidden on sm and xs). |
| 70 | `<div class="hide visible-xs-block visible-sm-block form-group">` | Replace with `<div class="d-block d-md-none form-group">`. |

---

## templates/schedule/detail.twig

Schedule editor page (main scheduler view). Complex page with dropdowns and grid.

**Docs:** [Dropdowns](https://getbootstrap.com/docs/4.6/components/dropdowns/), [Breadcrumb](https://getbootstrap.com/docs/4.6/components/breadcrumb/), [Grid](https://getbootstrap.com/docs/4.6/layout/grid/), [Buttons](https://getbootstrap.com/docs/4.6/components/buttons/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 11-15 | `<ul class="breadcrumb h-jail">` | Wrap in `<nav>`, use `<ol>`, add `.breadcrumb-item` to each `<li>`. |
| 18 | `col-xs-8` | Replace with `col-8`. |
| 22 | `col-xs-4` | Replace with `col-4`. |
| 23 | `pull-right` on btn-group | Replace with `float-right`. |
| 25 | `<span class="caret"></span>` | Remove (auto-generated by `.dropdown-toggle`). |
| 27-37 | `<li><a>` items in dropdown | Add `class="dropdown-item"` to each `<a>`. Remove `<li>` wrappers or keep them but add `.dropdown-item`. |
| 30, 35 | `<li class="divider"></li>` | Replace with `<div class="dropdown-divider"></div>`. |
| 110 | `col-xs-offset-4` | Replace with `offset-4`. |
| 110 | `col-xs-4` | Replace with `col-4`. |
| 118 | Same offsets | Same changes. |

---

## templates/schedule/form.twig

Schedule create/edit form.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Input Group](https://getbootstrap.com/docs/4.6/components/input-group/), [Cards](https://getbootstrap.com/docs/4.6/components/card/), [Breadcrumb](https://getbootstrap.com/docs/4.6/components/breadcrumb/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 7-16 | `<ul class="breadcrumb h-jail">` | Wrap in `<nav>`, use `<ol>`, add `.breadcrumb-item` to each `<li>`. |
| 26 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 27 | `class="form-horizontal"` | Remove. Add `row` to each `.form-group`. |
| 35-36 | `<label class="col-lg-3 control-label"` | Replace `control-label` with `col-form-label`. |
| 37 | `<p class="form-control-static` | Replace with `<p class="form-control-plaintext`. |
| 41-95 | All form-groups with `control-label` | Add `row` to `.form-group`, replace `control-label` with `col-form-label`. |
| 62 | `<div class="input-group-addon">/{{ event.slug }}/</div>` | Replace with `<div class="input-group-prepend"><span class="input-group-text">/{{ event.slug }}/</span></div>`. |
| 98 | `<div class="input-group-addon">https://twitch.tv/</div>` | Replace with `<div class="input-group-prepend"><span class="input-group-text">https://twitch.tv/</span></div>`. |
| 118 | `<div class="input-group-addon">@</div>` | Replace with `<div class="input-group-prepend"><span class="input-group-text">@</span></div>`. |
| 138 | `<div class="input-group-addon">@</div>` | Same as above. |
| 90 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 109, 129, 147 | `<span class="help-block">` | Same replacement. |
| 168 | `col-xs-7`, `col-xs-5` | Replace with `col-7`, `col-5`. |
| 209 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 277 | `<span class="hidden-xs">Side</span><span class="hidden-lg hidden-md hidden-sm">Foot</span>` | Replace with `<span class="d-none d-sm-inline">Side</span><span class="d-sm-none">Foot</span>`. |
| 312 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 323 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 332 | `<span class="help-block">` | Same. |
| 339 | `<div class="panel panel-default remarkable-preview-panel">` | Replace with `<div class="card remarkable-preview-panel">`. |
| 340 | `<div class="panel-body remarkable-preview"` | Replace with `<div class="card-body remarkable-preview"`. |
| 367 | `col-xs-4` | Replace with `col-4`. |

---

## templates/schedule/confirmation.twig

Schedule deletion confirmation.

**Docs:** [Breadcrumb](https://getbootstrap.com/docs/4.6/components/breadcrumb/), [Grid](https://getbootstrap.com/docs/4.6/layout/grid/), [Buttons](https://getbootstrap.com/docs/4.6/components/buttons/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 7-12 | `<ul class="breadcrumb h-jail">` | Wrap in `<nav>`, use `<ol>`, add `.breadcrumb-item` to each `<li>`. |
| 24 | `col-lg-offset-3`, `col-md-offset-3`, `col-sm-offset-2` | Replace with `offset-lg-3`, `offset-md-3`, `offset-sm-2`. |
| 24 | `col-xs-6` | Replace with `col-6`. |
| 25 | `btn btn-default btn-sm` | Replace `btn-default` with `btn-secondary`. |
| 28 | `col-xs-6` | Replace with `col-6`. |

---

## templates/schedule/columns.twig

Column editor page (Knockout.js driven).

**Docs:** [Breadcrumb](https://getbootstrap.com/docs/4.6/components/breadcrumb/), [Grid](https://getbootstrap.com/docs/4.6/layout/grid/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 7-12 | `<ul class="breadcrumb h-jail">` | Wrap in `<nav>`, use `<ol>`, add `.breadcrumb-item` to each `<li>`. |
| 50-51 | `col-lg-offset-4`, `col-md-offset-4`, `col-sm-offset-3`, `col-xs-offset-4` | Replace with `offset-lg-4`, `offset-md-4`, `offset-sm-3`, `offset-4`. |
| 50 | `col-xs-4` | Replace with `col-4`. |
| 58 | Same offsets | Same changes. |
| 58 | `col-xs-6`, `col-xs-offset-3` | Replace with `col-6`, `offset-3`. |

---

## templates/schedule/column.twig

Single column Knockout.js template (included in columns.twig).

**Docs:** [Buttons](https://getbootstrap.com/docs/4.6/components/buttons/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 12-14 | `btn-xs` (in data-bind attr expressions) | Replace with `btn-sm` (inside Knockout `attr` bindings -- `btn-xs` is removed in BS4). |
| 16 | `btn btn-danger btn-xs` | Replace `btn-xs` with `btn-sm`. |
| 20-21 | `btn btn-danger btn-xs`, `btn btn-default btn-xs` | Replace `btn-xs` with `btn-sm`, `btn-default` with `btn-secondary`. |

---

## templates/schedule/item.twig

Single schedule item row (Knockout.js template).

**Docs:** [Buttons](https://getbootstrap.com/docs/4.6/components/buttons/), [Utilities](https://getbootstrap.com/docs/4.6/utilities/text/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 28-29 | `btn-xs` | Replace with `btn-sm`. |
| 31-32 | `btn-xs`, `btn-default` | Replace `btn-xs` with `btn-sm`, `btn-default` with `btn-secondary`. |
| 33 | `btn btn-danger btn-xs` | Replace `btn-xs` with `btn-sm`. |
| 37-38 | `btn btn-danger btn-xs`, `btn btn-default btn-xs` | Same changes. |
| 46 | `<dl class="dl-horizontal">` | Replace with `<dl class="row">` and add `.col-sm-3` (or similar) to `<dt>` and `.col-sm-9` to `<dd>`. See [Typography - Description lists](https://getbootstrap.com/docs/4.6/content/typography/#description-list-alignment). |

---

## templates/schedule/import.twig

Schedule import page.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Cards](https://getbootstrap.com/docs/4.6/components/card/), [Breadcrumb](https://getbootstrap.com/docs/4.6/components/breadcrumb/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 9-14 | `<ul class="breadcrumb h-jail">` | Wrap in `<nav>`, use `<ol>`, add `.breadcrumb-item` to each `<li>`. |
| 24 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 25 | `class="form-horizontal"` | Remove. Add `row` to each `.form-group`. |
| 31 | `<label class="col-lg-3 control-label"` | Replace `control-label` with `col-form-label`. Add `row` to parent form-group. |
| 35 | `<span class="help-block">` (inside macro) | Already handled via macros.twig change. |
| 36-45 | `<div class="checkbox"><label><input type="checkbox">` | Replace with BS4 custom check: `<div class="form-check"><input class="form-check-input" type="checkbox"><label class="form-check-label">`. |
| 60 | `<span class="hidden-xs">Side</span><span class="hidden-lg hidden-md hidden-sm">Foot</span>` | Replace with `<span class="d-none d-sm-inline">Side</span><span class="d-sm-none">Foot</span>`. |

---

## templates/schedule/import-result.twig

Import result page.

**Docs:** [Breadcrumb](https://getbootstrap.com/docs/4.6/components/breadcrumb/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 9-14 | `<ul class="breadcrumb h-jail">` | Wrap in `<nav>`, use `<ol>`, add `.breadcrumb-item` to each `<li>`. |

No other BS3-specific classes are used.

---

## templates/frontend/layout.twig

Minimal layout extension for public-facing pages. No Bootstrap classes present.

### Changes Required

No changes needed. This file only overrides blocks and contains no Bootstrap-specific classes.

---

## templates/frontend/event/event.twig

Public event page showing list of schedules.

**Docs:** [Navbar](https://getbootstrap.com/docs/4.6/components/navbar/), [Cards](https://getbootstrap.com/docs/4.6/components/card/), [Dropdowns](https://getbootstrap.com/docs/4.6/components/dropdowns/), [Grid](https://getbootstrap.com/docs/4.6/layout/grid/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 24-32 | Navbar header with `.navbar-toggle` and icon-bars | Same changes as in layout.twig: replace with `.navbar-toggler` + `.navbar-toggler-icon`. Remove `.navbar-header` wrapper. |
| 36 | `<ul class="nav navbar-nav">` | Replace with `<ul class="navbar-nav mr-auto">`. |
| 37-47 | `<li>`, `<li class="dropdown">`, dropdown-toggle with caret | Add `.nav-item`/`.nav-link`, remove `<span class="caret">`, add `.dropdown-item` to menu items. |
| 51 | `<ul class="nav navbar-nav navbar-right">` | Replace with `<ul class="navbar-nav ml-auto">`. |
| 52-55 | `<li><a>` items with `hidden-sm` | Add `.nav-item`/`.nav-link`. Replace `hidden-sm` with `d-sm-none d-md-inline` or `d-none d-md-inline`. |
| 62 | `col-xs-12` | Replace with `col-12`. |
| 70 | `col-lg-offset-3`, `col-md-offset-2`, `col-sm-offset-1` | Replace with `offset-lg-3`, `offset-md-2`, `offset-sm-1`. |
| 70 | `col-xs-12` | Replace with `col-12`. |
| 79 | `col-xs-12` | Replace with `col-12`. |
| 83 | `<div class="well well-sm">` | Replace with `<div class="card card-body p-2">`. |
| 85 | `<span class="badge">` | Still valid in BS4. No change. |

---

## templates/frontend/schedule/schedule.twig

Public schedule view page (the main user-facing schedule).

**Docs:** [Navbar](https://getbootstrap.com/docs/4.6/components/navbar/), [Panels/Cards](https://getbootstrap.com/docs/4.6/components/card/), [Dropdowns](https://getbootstrap.com/docs/4.6/components/dropdowns/), [Grid](https://getbootstrap.com/docs/4.6/layout/grid/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 35-43 | Navbar header with `.navbar-toggle` and icon-bars | Same as layout.twig: `.navbar-toggler` + `.navbar-toggler-icon`. Remove `.navbar-header`. |
| 47 | `<ul class="nav navbar-nav">` | Replace with `<ul class="navbar-nav mr-auto">`. |
| 48-78 | All `<li>`, dropdown toggles with `<span class="caret">` | Add `.nav-item`/`.nav-link`. Remove caret spans. Add `.dropdown-item` to items in dropdown menus. |
| 70 | `<li class="divider"></li>` | Replace with `<div class="dropdown-divider"></div>`. |
| 86 | `<ul class="nav navbar-nav navbar-right">` | Replace with `<ul class="navbar-nav ml-auto">`. |
| 88-99 | Items with `hidden-sm` | Add `.nav-item`/`.nav-link`. Replace `hidden-sm` with `d-none d-md-inline` (or appropriate display utility). |
| 106 | `col-xs-12` | Replace with `col-12`. |
| 114 | `col-lg-offset-3`, `col-md-offset-2`, `col-sm-offset-1` | Replace with `offset-lg-3`, `offset-md-2`, `offset-sm-1`. |
| 114 | `col-xs-12` | Replace with `col-12`. |
| 205-206 | `col-lg-offset-2`, `col-md-offset-2`, `col-sm-offset-1` | Replace with `offset-lg-2`, `offset-md-2`, `offset-sm-1`. |
| 206-211 | `<div class="panel panel-success h-current">`, `panel-heading`, `panel-title`, `panel-body` | Replace with `<div class="card border-success h-current">`, `card-header`, `card-title` (or use heading inside card-header), `card-body`. |
| 208 | `pull-right` | Replace with `float-right`. |
| 215-225 | `<div class="panel panel-default h-next">`, `panel-heading`, `panel-title`, `panel-body` | Replace with `<div class="card h-next">`, `card-header`, heading inside card-header, `card-body`. |
| 231 | `btn-xs btn-default` | Replace `btn-xs` with `btn-sm`, `btn-default` with `btn-secondary`. |
| 248 | `<dl class="dl-horizontal">` | Replace with `<dl class="row">` + column classes on `<dt>`/`<dd>`. |

---

## templates/frontend/schedule/ical.twig

iCal feed info page.

**Docs:** [Navbar](https://getbootstrap.com/docs/4.6/components/navbar/), [Dropdowns](https://getbootstrap.com/docs/4.6/components/dropdowns/), [Buttons](https://getbootstrap.com/docs/4.6/components/buttons/), [Grid](https://getbootstrap.com/docs/4.6/layout/grid/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 29-35 | Navbar header with `.navbar-toggle` and icon-bars | Same as layout.twig: `.navbar-toggler` + `.navbar-toggler-icon`. Remove `.navbar-header`. |
| 41 | `<ul class="nav navbar-nav">` | Replace with `<ul class="navbar-nav mr-auto">`. |
| 41-63 | Dropdowns with `<span class="caret">`, `<li class="divider">` | Same dropdown changes: remove caret, add nav-item/nav-link, dropdown-item, dropdown-divider. |
| 57 | `<li class="divider"></li>` | Replace with `<div class="dropdown-divider"></div>`. |
| 70 | `<ul class="nav navbar-nav navbar-right">` | Replace with `<ul class="navbar-nav ml-auto">`. |
| 70-84 | Items with `hidden-sm` | Add `.nav-item`/`.nav-link`. Replace `hidden-sm` with `d-none d-md-inline`. |
| 89 | `col-xs-12` | Replace with `col-12`. |
| 95 | `col-xs-12` | Replace with `col-12`. |
| 96 | `btn btn-default btn-sm` | Replace `btn-default` with `btn-secondary`. |
| 101 | `col-xs-12` | Replace with `col-12`. |
| 103 | `img-thumbnail pull-right` | Replace `pull-right` with `float-right`. |

---

## templates/frontend/schedule/not_found.twig

Schedule not found (404) page.

**Docs:** [Grid](https://getbootstrap.com/docs/4.6/layout/grid/), [Images](https://getbootstrap.com/docs/4.6/content/images/), [Navbar](https://getbootstrap.com/docs/4.6/components/navbar/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 25-32 | Navbar header with `.navbar-toggle` and icon-bars | Same navbar changes. |
| 37-57 | Same navbar nav patterns | Same nav-item/nav-link/dropdown changes. |
| 53-56 | `hidden-sm` | Replace with `d-none d-md-inline`. |
| 62 | `col-xs-4`, `col-xs-offset-4` | Replace with `col-4`, `offset-4`. |
| 64 | `class="img-responsive"` | Replace with `class="img-fluid"`. |

---

## templates/bundles/TwigBundle/Exception/error.html.twig

Generic 500 error page.

**Docs:** [Grid](https://getbootstrap.com/docs/4.6/layout/grid/), [Images](https://getbootstrap.com/docs/4.6/content/images/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 10 | `col-lg-offset-3`, `col-md-offset-3`, `col-sm-offset-3`, `col-xs-8`, `col-xs-offset-2` | Replace with `offset-lg-3`, `offset-md-3`, `offset-sm-3`, `col-8`, `offset-2`. |
| 12 | `class="img-responsive"` | Replace with `class="img-fluid"`. |
| 16 | `col-lg-offset-3`, `col-md-offset-2`, `col-sm-offset-1` | Replace with `offset-lg-3`, `offset-md-2`, `offset-sm-1`. |
| 16 | `col-xs-12` | Replace with `col-12`. |

---

## templates/bundles/TwigBundle/Exception/error403.html.twig

403 Forbidden error page.

**Docs:** [Grid](https://getbootstrap.com/docs/4.6/layout/grid/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 9 | `col-lg-offset-3`, `col-md-offset-2`, `col-sm-offset-1` | Replace with `offset-lg-3`, `offset-md-2`, `offset-sm-1`. |
| 9 | `col-xs-12` | Replace with `col-12`. |

---

## templates/bundles/TwigBundle/Exception/error404.html.twig

404 Not Found error page.

**Docs:** [Grid](https://getbootstrap.com/docs/4.6/layout/grid/), [Images](https://getbootstrap.com/docs/4.6/content/images/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 9 | `col-lg-offset-4`, `col-md-offset-4`, `col-sm-offset-4`, `col-xs-4`, `col-xs-offset-4` | Replace with `offset-lg-4`, `offset-md-4`, `offset-sm-4`, `col-4`, `offset-4`. |
| 11 | `class="img-responsive"` | Replace with `class="img-fluid"`. |

---

## templates/admin/layout.twig

Admin layout (extends backend.twig). Minimal file.

### Changes Required

No changes needed. This file only extends `backend.twig` and imports macros with no Bootstrap-specific classes.

---

## templates/admin/dashboard.twig

Admin dashboard with tile links.

**Docs:** [Cards](https://getbootstrap.com/docs/4.6/components/card/), [Grid](https://getbootstrap.com/docs/4.6/layout/grid/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 14 | `col-xs-12` | Replace with `col-12`. |
| 15 | `<div class="well well-sm">` | Replace with `<div class="card card-body p-2">`. |
| 24, 34, 47 | Same pattern | Same changes for each tile. |
| 24, 34, 47 | `col-xs-12` | Replace with `col-12`. |

---

## templates/admin/users/index.twig

Admin user list page.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/)

### Changes Required

No changes needed. The file uses `form-inline`, `form-group`, `form-control`, `sr-only`, `table`, `table-striped`, `table-hover`, `alert`, `alert-info` -- all valid in BS4. No `col-xs-*` or deprecated classes are present.

---

## templates/admin/users/view.twig

Admin user view (read-only).

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Cards](https://getbootstrap.com/docs/4.6/components/card/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 15 | `<div class="well form-horizontal">` | Replace with `<div class="card card-body">`. Remove `form-horizontal`. |
| 20 | `<label class="col-lg-4 control-label"` | Replace `control-label` with `col-form-label`. |
| 20 | Each `<div class="form-group">` | Add `row` class. |
| 22 | `<p class="form-control-static">` | Replace with `<p class="form-control-plaintext">`. |
| 27-29, 33-35, 39-41, 45-47, 49-51, 55-57, 62-66 | Same form-group pattern | Add `row`, replace `control-label` with `col-form-label`, replace `form-control-static` with `form-control-plaintext`. |

---

## templates/admin/users/form.twig

Admin user edit form.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Cards](https://getbootstrap.com/docs/4.6/components/card/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 15 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 16 | `class="form-horizontal"` | Remove. Add `row` to each `.form-group`. |
| 22-23 | `<label class="col-lg-4 control-label"` | Replace `control-label` with `col-form-label`. |
| 26 | `<p class="form-control-static">` | Replace with `<p class="form-control-plaintext">`. |
| 27 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 22-108 | All form-groups | Add `row`, replace `control-label` with `col-form-label`. |
| 27, 47, 71 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 93-103 | `<div class="radio"><label><input type="radio">` | Replace with BS4 form-check: `<div class="form-check"><input class="form-check-input" type="radio"><label class="form-check-label">`. |
| 119 | `<div class="well">` (password form) | Replace with `<div class="card card-body">`. |
| 120 | `class="form-horizontal"` | Remove. Add `row` to form-groups. |
| 126-128 | form-group + control-label | Add `row`, replace `control-label` with `col-form-label`. |
| 134-136 | Same | Same. |

---

## templates/admin/events/index.twig

Admin event list page.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/)

### Changes Required

No changes needed. Same as admin/users/index.twig -- uses only valid BS4 classes.

---

## templates/admin/events/view.twig

Admin event view (read-only).

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Cards](https://getbootstrap.com/docs/4.6/components/card/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 18 | `<div class="well form-horizontal">` | Replace with `<div class="card card-body">`. Remove `form-horizontal`. |
| 23 | `<label class="col-lg-4 control-label">` | Replace `control-label` with `col-form-label`. |
| 22-88 | All `<div class="form-group">` | Add `row`. |
| 25 | `<p class="form-control-static` | Replace with `<p class="form-control-plaintext`. |
| All | Every `form-control-static` | Replace with `form-control-plaintext`. |
| All | Every `control-label` | Replace with `col-form-label`. |
| 106 | `col-xs-12` | Replace with `col-12`. |
| 109 | `<div class="well well-sm">` | Replace with `<div class="card card-body p-2">`. |

---

## templates/admin/events/form.twig

Admin event edit form.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Input Group](https://getbootstrap.com/docs/4.6/components/input-group/), [Cards](https://getbootstrap.com/docs/4.6/components/card/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 18 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 19 | `class="form-horizontal"` | Remove. Add `row` to each `.form-group`. |
| 25-26 | `control-label` | Replace with `col-form-label`. |
| 28 | `<p class="form-control-static` | Replace with `<p class="form-control-plaintext`. |
| 32-134 | All form-groups | Add `row`, replace `control-label` with `col-form-label`. |
| 60 | `<div class="input-group-addon">http://twitch.tv/</div>` | Replace with `<div class="input-group-prepend"><span class="input-group-text">http://twitch.tv/</span></div>`. |
| 70 | `<div class="input-group-addon">@</div>` | Replace with `<div class="input-group-prepend"><span class="input-group-text">@</span></div>`. |
| 80 | `<div class="input-group-addon">@</div>` | Same. |
| 114 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 122-126 | `<div class="checkbox"><label><input type="checkbox">` | Replace with BS4 form-check pattern. |
| 150 | `col-xs-12` | Replace with `col-12`. |
| 153 | `<div class="well well-sm">` | Replace with `<div class="card card-body p-2">`. |

---

## templates/admin/events/confirmation.twig

Admin event deletion confirmation.

**Docs:** [Grid](https://getbootstrap.com/docs/4.6/layout/grid/), [Buttons](https://getbootstrap.com/docs/4.6/components/buttons/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 30 | `col-lg-offset-3`, `col-md-offset-3`, `col-sm-offset-2` | Replace with `offset-lg-3`, `offset-md-3`, `offset-sm-2`. |
| 30 | `col-xs-6` | Replace with `col-6`. |
| 31 | `btn btn-default btn-sm` | Replace `btn-default` with `btn-secondary`. |
| 34 | `col-xs-6` | Replace with `col-6`. |

---

## templates/admin/schedules/index.twig

Admin schedule list page.

### Changes Required

No changes needed. Same pattern as admin/users/index.twig and admin/events/index.twig -- only uses BS4-compatible classes.

---

## templates/admin/schedules/view.twig

Admin schedule view (read-only).

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Cards](https://getbootstrap.com/docs/4.6/components/card/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 18 | `<div class="well form-horizontal">` | Replace with `<div class="card card-body">`. Remove `form-horizontal`. |
| 23 | `<label class="col-lg-4 control-label">` | Replace `control-label` with `col-form-label`. |
| 22-109 | All `<div class="form-group">` | Add `row`. |
| 25-106 | All `<p class="form-control-static">` | Replace with `<p class="form-control-plaintext">`. |
| 23-103 | All `control-label` | Replace with `col-form-label`. |

---

## templates/admin/schedules/form.twig

Admin schedule edit form.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Input Group](https://getbootstrap.com/docs/4.6/components/input-group/), [Cards](https://getbootstrap.com/docs/4.6/components/card/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 18 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 19 | `class="form-horizontal"` | Remove. Add `row` to each `.form-group`. |
| 24-25 | `control-label` | Replace with `col-form-label`. |
| 27 | `<p class="form-control-static` | Replace with `<p class="form-control-plaintext`. |
| 30-32 | Same | Same. |
| 39-164 | All form-groups with `control-label` | Add `row`, replace `control-label` with `col-form-label`. |
| 51 | `<div class="input-group-addon">/{{ event.slug }}/</div>` | Replace with `<div class="input-group-prepend"><span class="input-group-text">/{{ event.slug }}/</span></div>`. |
| 70 | `<div class="input-group-addon">https://twitch.tv/</div>` | Replace with `<div class="input-group-prepend"><span class="input-group-text">https://twitch.tv/</span></div>`. |
| 80 | `<div class="input-group-addon">@</div>` | Replace with `<div class="input-group-prepend"><span class="input-group-text">@</span></div>`. |
| 90 | `<div class="input-group-addon">@</div>` | Same. |
| 116 | `col-xs-7`, `col-xs-5` | Replace with `col-7`, `col-5`. |
| 154 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |

---

## templates/admin/schedules/confirmation.twig

Admin schedule deletion confirmation.

**Docs:** [Grid](https://getbootstrap.com/docs/4.6/layout/grid/), [Buttons](https://getbootstrap.com/docs/4.6/components/buttons/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 30 | `col-lg-offset-3`, `col-md-offset-3`, `col-sm-offset-2` | Replace with `offset-lg-3`, `offset-md-3`, `offset-sm-2`. |
| 30 | `col-xs-6` | Replace with `col-6`. |
| 31 | `btn btn-default btn-sm` | Replace `btn-default` with `btn-secondary`. |
| 34 | `col-xs-6` | Replace with `col-6`. |

---

## templates/admin/utils/layout.twig

Admin utilities layout with nav tabs.

**Docs:** [Navs](https://getbootstrap.com/docs/4.6/components/navs/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 7-11 | `<ul class="nav nav-tabs"><li class="active">` | Add `.nav-item` to each `<li>`. Add `.nav-link` to each `<a>`. Move `.active` class from `<li>` to the `<a>` element. Use the conditional to add `active` to the `<a>` instead. |

Example transformation:
```twig
{# Before #}
<li{% if active == 'config' %} class="active"{% endif %}><a href="...">...</a></li>
{# After #}
<li class="nav-item"><a href="..." class="nav-link{% if active == 'config' %} active{% endif %}">...</a></li>
```

---

## templates/admin/utils/config.twig

Admin configuration form.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Input Group](https://getbootstrap.com/docs/4.6/components/input-group/), [Cards](https://getbootstrap.com/docs/4.6/components/card/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 8 | `<div class="well">` | Replace with `<div class="card card-body">`. |
| 9 | `class="form-horizontal"` | Remove. Add `row` to each `.form-group`. |
| 13-168 | All `<label class="col-lg-3 control-label"` | Replace `control-label` with `col-form-label`. |
| 13-168 | All `<div class="form-group...">` | Add `row`. |
| 22-23 | `<span class="help-block">` | Replace with `<small class="form-text text-muted">`. |
| 33 | `<div class="input-group-addon"> seconds</div>` | Replace with `<div class="input-group-append"><span class="input-group-text"> seconds</span></div>`. |
| 37 | `<span class="help-block">` | Same replacement. |
| 51-52 | `<span class="help-block">` | Same. |
| 68-69 | `<span class="help-block">` | Same. |
| 86-87 | `<span class="help-block">` | Same. |
| 100-103 | `<span class="help-block">` | Same. |
| 113 | `<div class="input-group-addon"> per user</div>` | Replace with `<div class="input-group-append"><span class="input-group-text"> per user</span></div>`. |
| 118-120 | `<span class="help-block">` | Same replacement. |
| 131 | `<div class="input-group-addon"> per event</div>` | Replace with `<div class="input-group-append"><span class="input-group-text"> per event</span></div>`. |
| 135-138 | `<span class="help-block">` | Same. |
| 149 | `<div class="input-group-addon"> per schedule</div>` | Replace with `<div class="input-group-append"><span class="input-group-text"> per schedule</span></div>`. |
| 153-156 | `<span class="help-block">` | Same. |
| 166 | `<span class="help-block">` | Same. |
| 171 | `col-lg-offset-3` | Replace with `offset-lg-3`. |

---

## templates/admin/utils/tools.twig

Admin tools page.

**Docs:** [Buttons](https://getbootstrap.com/docs/4.6/components/buttons/), [Grid](https://getbootstrap.com/docs/4.6/layout/grid/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 12 | `btn btn-default btn-sm btn-block` | Replace `btn-default` with `btn-secondary`. |
| 9, 16 | `col-xs-6` | Replace with `col-6`. |
| 23, 30 | Same | Same. |
| 25 | `btn btn-default btn-sm btn-block` | Replace `btn-default` with `btn-secondary`. |
| 38 | `btn btn-default btn-sm btn-block` | Replace `btn-default` with `btn-secondary`. |
| 37, 44 | `col-xs-6` | Replace with `col-6`. |

---

## templates/admin/utils/serverinfo.twig

Admin server info page.

**Docs:** [Forms](https://getbootstrap.com/docs/4.6/components/forms/), [Cards](https://getbootstrap.com/docs/4.6/components/card/)

### Changes Required

| Line | Current | Required Change |
|------|---------|-----------------|
| 11 | `<div class="well form-horizontal">` | Replace with `<div class="card card-body">`. Remove `form-horizontal`. |
| 12-13 | `<label class="col-lg-4 control-label">` | Replace `control-label` with `col-form-label`. |
| 12, 18 | `<div class="form-group">` | Add `row`. |
| 14, 20 | `<p class="form-control-static">` | Replace with `<p class="form-control-plaintext">`. |

---

## Global Summary of Recurring Changes

These patterns appear across many files and should be addressed systematically:

| Pattern | Occurrences | Migration |
|---------|-------------|-----------|
| `col-xs-*` | ~50+ | Replace with `col-*` (drop `xs` infix) |
| `col-*-offset-*` | ~30+ | Replace with `offset-*-*` (e.g. `col-lg-offset-3` -> `offset-lg-3`) |
| `.well` / `.well-sm` | ~25+ | Replace with `.card .card-body` (+ `p-2` for small variant) |
| `.panel` / `.panel-*` | ~6 | Replace with `.card` / `.card-header` / `.card-body` / `.card-footer` |
| `control-label` | ~60+ | Replace with `col-form-label` |
| `form-horizontal` | ~20+ | Remove class. Add `row` to each `.form-group` |
| `form-control-static` | ~15+ | Replace with `form-control-plaintext` |
| `.help-block` | ~30+ | Replace with `.form-text .text-muted` (preferably on a `<small>`) |
| `.input-group-addon` | ~20+ | Replace with `.input-group-prepend`/`.input-group-append` > `.input-group-text` |
| `btn-default` | ~15+ | Replace with `btn-secondary` |
| `btn-xs` | ~20+ | Replace with `btn-sm` (btn-xs removed) |
| `.navbar-default` | 1 | Replace with `.navbar-light` |
| `.navbar-toggle` + icon-bars | 5 | Replace with `.navbar-toggler` + `.navbar-toggler-icon` |
| `.navbar-header` | 5 | Remove wrapper div |
| `navbar-right` / `navbar-left` | ~10 | Replace with `ml-auto` / `mr-auto` (flexbox) |
| `.navbar-form` | 1 | Replace with `.form-inline` |
| `<span class="caret"></span>` | ~6 | Remove (auto-generated by `.dropdown-toggle`) |
| `.divider` (in dropdowns) | ~8 | Replace with `.dropdown-divider` on a `<div>` |
| `nav navbar-nav` items without `.nav-item`/`.nav-link` | ~50+ | Add `.nav-item` to `<li>`, `.nav-link` to `<a>` |
| `.hidden-xs` / `.hidden-sm` / `.hidden-md` / `.hidden-lg` | ~10 | Replace with `.d-none .d-{bp}-{display}` pattern |
| `.visible-xs-*` / `.visible-sm-*` | ~3 | Replace with `.d-{display} .d-{bp}-none` pattern |
| `.pull-left` / `.pull-right` | ~4 | Replace with `.float-left` / `.float-right` |
| `.img-responsive` | ~3 | Replace with `.img-fluid` |
| `dl-horizontal` | ~2 | Replace with `row` on `<dl>` + column classes on `<dt>`/`<dd>` |
| `.breadcrumb` (bare `<li>`) | ~15 | Wrap in `<nav>`, use `<ol>`, add `.breadcrumb-item` to each `<li>` |
| Nav tabs `active` on `<li>` | 1 (utils/layout) | Move `active` to `<a>`, add `.nav-item`/`.nav-link` |
| `.checkbox` wrapper pattern | ~3 | Replace with `.form-check` / `.form-check-input` / `.form-check-label` |
| `.radio` wrapper pattern | 1 (admin users form) | Replace with `.form-check` / `.form-check-input` / `.form-check-label` |

---

## Recommended Migration Order

1. **macros.twig** -- since it's imported everywhere, fix it first (help-block, btn-default).
2. **layout.twig** -- fix the navbar (most impactful, affects every page).
3. **navigation.twig** -- complete the navbar changes (dropdowns, nav items).
4. **Forms** -- systematically address `form-horizontal`, `control-label`, `help-block`, `input-group-addon` across all form templates.
5. **Wells to Cards** -- replace all `.well` with `.card .card-body`.
6. **Panels to Cards** -- replace the few `.panel` usages.
7. **Grid utilities** -- search-and-replace `col-xs-*` -> `col-*` and `col-*-offset-*` -> `offset-*-*`.
8. **Buttons** -- replace `btn-default` -> `btn-secondary` and `btn-xs` -> `btn-sm`.
9. **Breadcrumbs** -- add `.breadcrumb-item` and proper wrapping.
10. **Display/visibility utilities** -- replace `hidden-*` / `visible-*` with `d-*` utilities.
11. **Remaining** -- `pull-right`/`pull-left`, `img-responsive`, `dl-horizontal`, nav tabs.
12. **Frontend/public pages** -- these have their own navbar instances that need the same treatment.
