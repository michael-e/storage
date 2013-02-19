# Storage

This Symphony extension creates a front-end storage using native PHP sessions.

Storage allows the creation of nested data arrays of any kind with three restrictions:

- The first key level in the storage array is considered the group name, this allows the creation of different storage contexts. For example a shopping cart and a list of user settings.
- Each item can contain a reserved key `count` which is used to store the amount of an item. Storage will automatically recalculate this count on update which is usefull for creating inventories.
- Each item can contain a reserved key `count-positive` which behaves exactly the same as `count` except that fact, that it doesn't allow negative values. This is usefull for creating shopping carts where clients are not supposed to order negative amount of items.

All count value have to be integers, float values will be considered invalid and will be ignored.

## Events

Storage is a standalone class (`/lib/class.storage.php`) that can be used to create custom events. But the extension bundles a default event that should be sufficient for most cases. It offers three actions:

- **set:** to set new groups and items, replacing existing values
- **set-count:** to set new groups and items, replacing existing values and recalculating counts
- **drop:** to drop entire groups or single items from the storage

These actions can be triggered by either a `POST` or a `GET` request. This form for example will update a shopping basket by raising the amount of `article1` by 5.

	<form action="" method="post">
		<input name="storage[basket][article1][count-positive]" value="5" />
		<input name="storage-action[update]" type="submit" />
	</form>

## Data Sources

Storage also bundles a custom Data Source interface offering filtering by groups. If no filters have been specified, the Data source will return the full storage.

Optionally, it's possible to output the selected groups as parameters. Those output parameters will follow the Symphony naming convention of `$ds-` + Data Source name + `.` + group name, e. g. `$ds-storage.basket` and will contain the ids of the group's direct child items:

### Parameter Pool

    $ds-storage.basket: 'article1, article2, article3'

### XML

    <ds-storage.basket>
        <item handle="article1">article1</item>
        <item handle="article2">article2</item>
        <item handle="article3">article3</item>
    </ds-storage.basket>
