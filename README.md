# EXT:error_log

Log 404 errors to separated backend module.

## Slug check

404 URIs that point to an existing page are not listed in the backend module,
because they do not need a redirect. A page matches, if it is not deleted, has
a frontend doktype and its slug (including the base path of the matching site
language) belongs to the site of the requested domain.
