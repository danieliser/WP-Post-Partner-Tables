For a long time I have been looking for a way to take full advantage of the Custom Post Type infrastructure such as UI and helper functions, but while still adding the capabilites of custom tables such as advanced search querying without the overhead of searching postmeta.

__Introducing WP Post Partner Tables.__ The Partner_Table class is a base class used to easily create custom tables that extend the typical post table with any extra info you want.

Example usages could include location data, payment information, statistics etc. These can be queried in any way you choose and then join the post data to the results when needed.

This repo includes a working location table and child class with an example search function to find posts by zipcode. A few helper functions are thrown in just for good measure.

To make saving, fetching and deleting info as easy as possible this hooks the get_post_meta, add_post_meta, update_post_meta & delete_post_meta functions to quickly integrate into your existing setups.

EX..

```
update_post_meta( 1, 'pt_location', array(
    'city'    => 'Jacksonville',
    'state'   => 'Florida',
    'zipcode' => 32204,
    'lat'     => 30.315243,
    'long'    => -81.685681,
) );
```

```
$location = get_post_meta( 1, 'pt_location' );
echo $location['city'] . ' ' . $location['state'] . ' ' . $location['zipcode'];
```

```
delete_post_meta( 1, 'pt_location' );
```

If you enable auto_join you can use the global $post with column names as keys like `$post->city`, `$post->state` etc.

Future features will include:
* full caching layer
* support for multiple row
* user partner tables
* comment partner tables

__Feel free to submit issues and pull requests.__