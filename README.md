# Dadolun_CategoryImport module for Magento 2

Category importer from CSV file. 
This is a basic example and startup for more detailed category imports.
Allows to import / update categories from Magento shops.
Generates and updates categories by specified textual path. Path also defines hierarchy between categories.

### Features

- Adds category_code attribute for mapping
- Import categories from uploaded csv file matching paths made with category names
- Works only for admin store, categories are generated as available for each store

## Install module

- add module via composer or download and copy files to app/code/Dadolun/CategoryImport
- run bin/magento module:enable Dadolun_CategoryImport in command line

## How to extend

This module is easily extendible adding additional attribute on import.
Steps you should do:
- Add your columns to the csv
- Add category_import.xml file on your custom module like this:
```
<?xml version="1.0"?>
<additional name="custom">
    <column csv_name="your_field_csv_name" attribute_name="your_attribute_name" sort="6"></column>
    ...
</additional>
```
- Make a plugin on Dadolun\CategoryImport\Model\Importer\Category manageAdditionalCategoryData method 

Your method must add data to the loaded Category like manageAdditionalCategoryData original method.
You can change the order of the additional attributes setting the "sort" attribute on column tags.
Keep in mind that the original csv structure has 5 columns so you must start from column 6 with your attributes.

## Usage

**_NOTICES:_**
- keep in mind that parent categories must specified first or you'll get errors on execution
- use comma (',') as delimiter in file or change it by configuration on Stores > Configuration > Dadolun > Category Import

## Basic usage example

```
bin/magento dadolun:import:categories
```

## Simple CSV file example

```
category_code,path,sort_order,is_active,description
a_category,"A Category",999,1,"A Category Description"
c_category,"A Category,C Category",999,1,"C Category Description"
d_category,"A Category,D Category",999,1,"D Category Description"
e_category,"A Category,D Category,E Category",999,0,"E Category Description"
b_category,"B Category",999,1,"B Category Description"
```

## Contributing
Contributions are very welcome. In order to contribute, please fork this repository and submit a [pull request](https://docs.github.com/en/free-pro-team@latest/github/collaborating-with-issues-and-pull-requests/creating-a-pull-request).
