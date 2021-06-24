# Appsero Helper

Appsero Helper provide a connection between your Plugin or Theme users and their sites to your WordPress store.

AppSero offers Analytics, Licensing and Release system for Premium Plugins and Themes.

## Installation

1. Upload the `appsero-helper` directory to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit the 'Settings > Appsero Helper' menu item in your admin sidebar

On Appsero Helper settings page you have to connect your store with AppSero using API key.

## Shortcodes

Shortcodes will be used in any pages.

- [appsero_licenses] show licenses
- [appsero_orders] show orders
- [appsero_downloads] show downloads
- [appsero_my_account] My Account page


## Hooks to Extend Appsero My Account


### before_appsero_myaccount_sidebar
This action will be run just before the sidebar is displayed in appsero my account page.
This is preliminary for adding additional navigation in appsero my account. However you can add another card, details or any other things as you wish.

##### Definition
```
do_action( 'before_appsero_myaccount_sidebar', $tab );
```

##### Parameters
| Variable Name | Type   | Description   |
| ------------- | ----   | ------------  |
| $tab          | String | Current tab name. Actually it is the value of ` $_GET["tab"] ` |

#### Example
```
add_action( 'before_appsero_myaccount_sidebar', function ($tab) {
 
     echo '<li><a href="?tab=home" class="'. ($tab == 'home' ? 'ama-active-tab' : '') . '">Home</a></li>';

} );
```


### after__appsero_myaccount_sidebar
This action will be run just after the sidebar is displayed in appsero my account page.
This is preliminary for adding additional navigation in appsero my account. However you can add another card, details or any other things as you wish.

#### Definition
```
do_action( 'after_appsero_myaccount_sidebar', $tab );
```

#### Parameters
| Variable Name | Type   | Description   |
| ------------- | ----   | ------------  |
| $tab          | String | Current tab name. Actually it is the value of ` $_GET["tab"] ` |

```
add_action( 'after_appsero_myaccount_sidebar', function ($tab) {

     echo '<li><a href="?tab=custom" class="'. ($tab == 'custom' ? 'ama-active-tab' : '') . '">Custom Tab</a></li>';

} );
```


### before_appsero_myaccount_contents
This action will be run just before the tab content is displayed in appsero my account page.
This is preliminary for adding additional contents in all tabs of appsero myaccount pages.

#### Definition
```
do_action( 'before_appsero_myaccount_contents', $tab );
```

#### Parameters
| Variable Name | Type   | Description   |
| ------------- | ----   | ------------  |
| $tab          | String | Current tab name. Actually it is the value of ` $_GET["tab"] ` |

```
add_action( 'before_appsero_myaccount_contents', function ($tab) {

    echo '<img src="https://place-hold.it/500x100&text=before_appsero_myaccount_contents&fontsize=16" />';
    
} );
```


### after__appsero_myaccount_contents
This action will be run just after the tab content is displayed in appsero my account pages.
This is preliminary for adding additional contents in all tabs of appsero myaccount pages.

#### Definition
```
do_action( 'after_appsero_myaccount_contents', $tab );
```

#### Parameters
| Variable Name | Type   | Description   |
| ------------- | ----   | ------------  |
| $tab          | String | Current tab name. Actually it is the value of ` $_GET["tab"] ` |

```
add_action( 'after_appsero_myaccount_contents', function ($tab) {

    echo '<img src="https://place-hold.it/500x100&text=after_appsero_myaccount_contents&fontsize=16" />';

} );
```


### before_appsero_myaccount_download_table
The action will be run just before the downloads table displayed inside "Downloads" tab of appsero my account.

#### Definition
```
do_action( 'before_appsero_myaccount_download_table', $tab );
```

#### Parameters
| Variable Name | Type   | Description   |
| ------------- | ----   | ------------  |
| $tab          | String | Current tab name. Actually it is the value of ` $_GET["tab"] ` |

```
add_action( 'before_appsero_myaccount_download_table', function ($tab) {

    echo '<img src="https://place-hold.it/500x100&text=before_appsero_myaccount_download_table&fontsize=16" />';

} );
```



### after__appsero_myaccount_download_table
The action will be run just after the downloads table displayed inside "Downloads" tab of appsero my account.

#### Definition
```
do_action( 'after_appsero_myaccount_download_table', $tab );
```

#### Parameters
| Variable Name | Type   | Description   |
| ------------- | ----   | ------------  |
| $tab          | String | Current tab name. Actually it is the value of ` $_GET["tab"] ` |

```
add_action( 'after_appsero_myaccount_download_table', function ($tab) {

    echo '<img src="https://place-hold.it/500x100&text=after_appsero_myaccount_download_table&fontsize=16" />';

} );
```



### before_appsero_myaccount_license_table
The action will be run just before the licenses table displayed inside "Licenses" tab of appsero my account.

#### Definition
```
do_action( 'before_appsero_myaccount_license_table', $tab );
```

#### Parameters
| Variable Name | Type   | Description   |
| ------------- | ----   | ------------  |
| $tab          | String | Current tab name. Actually it is the value of ` $_GET["tab"] ` |

```
add_action( 'before_appsero_myaccount_license_table', function ($tab) {

    echo '<img src="https://place-hold.it/500x100&text=before_appsero_myaccount_license_table&fontsize=16" />';

} );
```



### after__appsero_myaccount_license_table
The action will be run just after the licenses table displayed inside "Licenses" tab of appsero my account.

#### Definition
```
do_action( 'after_appsero_myaccount_license_table', $tab );
```

#### Parameters
| Variable Name | Type   | Description   |
| ------------- | ----   | ------------  |
| $tab          | String | Current tab name. Actually it is the value of ` $_GET["tab"] ` |

```
add_action( 'after_appsero_myaccount_license_table', function ($tab) {

    echo '<img src="https://place-hold.it/500x100&text=after_appsero_myaccount_license_table&fontsize=16" />';

} );
```



### before_appsero_myaccount_order_table
The action will be run just before the orders table displayed inside "Orders" tab of appsero my account.

#### Definition
```
do_action( 'before_appsero_myaccount_order_table', $tab );
```

#### Parameters
| Variable Name | Type   | Description   |
| ------------- | ----   | ------------  |
| $tab          | String | Current tab name. Actually it is the value of ` $_GET["tab"] ` |

```
add_action( 'before_appsero_myaccount_order_table', function ($tab) {

    echo '<img src="https://place-hold.it/500x100&text=before_appsero_myaccount_order_table&fontsize=16" />';

} );
```



### after__appsero_myaccount_order_table
The action will be run just after the orders table displayed inside "Orders" tab of appsero my account.

#### Definition
```
do_action( 'after_appsero_myaccount_order_table', $tab );
```

#### Parameters
| Variable Name | Type   | Description   |
| ------------- | ----   | ------------  |
| $tab          | String | Current tab name. Actually it is the value of ` $_GET["tab"] ` |

```
add_action( 'after_appsero_myaccount_order_table', function ($tab) {

    echo '<img src="https://place-hold.it/500x100&text=after_appsero_myaccount_order_table&fontsize=16" />';

} );
```



### appsero_myaccount_custom_tab
You can add as many as custom tabs as you want using this hook. Inside your function, first check whether the current `$tab` is your defined tab and then write your codes.
However, you can not define your tab by appsero defined names "dashboard", "licenses", "downloads", "orders".

#### Definition
```
do_action( 'appsero_myaccount_custom_tab', $tab );
```

#### Parameters
| Variable Name | Type   | Description   |
| ------------- | ----   | ------------  |
| $tab          | String | Current tab name. Actually it is the value of ` $_GET["tab"] ` |

```
function my_custom_tab_content( $tab ) {

    if( $tab != "custom" ) {
        return;
    }

    echo '<br/><h1>This is my custom tab</h1><br/>';

}

add_action( 'appsero_myaccount_custom_tab', 'my_custom_tab_content');
```
