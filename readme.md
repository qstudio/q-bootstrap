# Q\Websites
**Contributors:** qlstudio  
**Tags:** Q, theme, framework, developers   
**Requires at least:** 5.0  
**Tested up to:** 5.5  
**Stable tag:** 0.0.3  
**License:** GPL3  

Websites Test for Tribe

## Architecture

- websites.php ( plugin config file )
- Plugin.php ( base plugin file )
- asset ( public js / css files )
- src
  - Helper - namespace Q\Websites\Helper
    - Log.php ( debugger )
  - Admin - namespace Q\Websites\Admin
    - CPT.php ( add CPT, meta boxes )
    - Manage.php ( manipulate cpt, get, set, edit, view, meta boxes )
    - Rewrite.php ( add rewrite rule )
    - Role.php ( to get user role data - might be redundant if api calls are simple )
  - View - namespace Q\Websites\View
    - Render.php ( render form + enqueue assets --> bootstrap.css / bootstrap.js / plugin.js )
  - Rest - namespace Q\Websites\Rest
    - Endpoint.php ( create end-point to retrieve data from cpt )
