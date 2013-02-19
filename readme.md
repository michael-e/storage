# Storage

This Symphony extension creates a front-end storage using native PHP sessions.

Storage allows the creation of nested data arrays of any kind with two restrictions:

- The first key level in the storage array is considered as group name, this allows the creation of different storage context. For example a shopping cart and a list of user settings.
- Each item can contain a reserved key `count` which is used to store the amount of an item. This is usefull for creating shopping carts or similar. Storage offers functions that automatically recalculate the amount on update.

## Events

Storage is a standalone class (`/lib/class.storage.php`) that can be used to create custom events. But the extension bundles a default event that should be sufficient for most cases. It offers three actions:

- **set:** to set new groups and items and replacing existing values
- **update:** to set new groups and item and replace existing values with updated item counts
- **delete:** to delete entire groups or single items

These actions can be triggered by either a `POST` or a `GET` request. This form for example will update a shopping basket by raising the amount of `article1` by 5.

	<form action="" method="post">
		<input name="storage[basket][article1][count]" value="5" />
		<input name="storage-action[update]" type="submit" />
	</form>

By default, the event makes sure that the item's count will never be lower than zero. If you need negative values, you can add the following to your form:

    <input name="storage-settings[allow-negative-counts] value="true" />

If you need different settings for different groups, you'll have to create a custom events using the Storage class.

## Data Sources

Storage also bundles a custom Data Source interface offering filtering by groups. If no filters have been specified, the Data source will return the full storage.

Optionally, it's possible to output the selected groups as parameters. Those output parameters will follow the Symphony naming convention of `$ds-` + Data Source name + `.` + group name, e. g. `$ds-storage.basket` and will contain the ids of the group's direct child items:

### Parameter Pool

    $ds-storage.basket: 'article1, article2, article3

### XML

    <ds-storage.basket>
        <item handle="article1">article1</item>
        <item handle="article2">article2</item>
        <item handle="article3">article3</item>
    </ds-storage.basket>
