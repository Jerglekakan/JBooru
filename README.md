# JBooru

A fork of Gelbooru v0.1.11.

The source code for version 0.1.9 of [Gelbooru](https://gelbooru.com) was publicly released in December 2008, but I found it was missing a few features present in the version used by the site itself and have added them here as well as some others that I have a need for.

This repository includes all v0.1.11 [updates](https://gelbooru.com/index.php?page=forum&s=view&id=99&pid=0).

## Installation

If you have a pre-existing Gelbooru installation, simply copy the repository files over to the installation folder and run any necessary scripts in `upgrades`.

Otherwise, you will need the following packages/software:

* PHP
* MySQL

Any version from the past, like, 15 years should be suitable. Once you have installed those, follow the steps outlined in [install/Readme.txt](install/Readme.txt). Additionaly, you may want to

* Adjust `max_execution_time` and `max_input_time` in your php.ini config file so the batch add script is not stopped prematurely
* Adjust `upload_max_filesize` in your php.ini config file to allow for larger uploads

## Features

#### Tag Categories

Tags can now be assigned to a category, thereby setting them apart from generic tags when viewing posts. When viewing a single post, tags of the same category will be grouped together, seperately from uncategorized tags, and will be colored differently if outlined that way in the site's stylesheet. When viewing multiple posts, tags will __NOT__ be grouped together by category, but they will be colored appropriately.

Tag categories can be created and deleted via a page added to the admin interface, but the category's css style must be manually added to the stylesheet. The stylesheet in this repository includes the styles used on Gelbooru.com for the Artist, Copyright, and Character categories.

#### Recursive Image Import

The `batch_add.php` script, which allows for the mass importing of large a quantity of images that are stored on the server but have not been added to the database, has been modified to be fully recursive. It searches the (configurable) directory `import` located in the same directory as the script and adds all images to the database. Every directory that the script encounters will have its name treated as a full tag and that tag will be added to every image inside that directory and its children.

For this feature you are advised to use the following `php.ini` config values:

* `max_execution_time = 0`
* `max_input_time = -1`

#### Batch Tag Operations

Some pages have been added to the admin interface allowing tag operations to be done on large groups of posts. Operations include

* Adding or removing tags from multiple posts
* Replacing one tag with another in multiple posts
* Removing posts from the database based on their tags, title or rating

Posts for these operations are selected by specifying IDs or evaluating a regular expression against a post's metadata (such as its tags, title, or source). Post IDs can be specified individually or as part of an inclusive range by inputting the two bounds of the range with a hyphen seperating them.

Example: `489 732 590 400-450 789`

#### Other

* A searchable tag listing page that also shows how many posts have each tag and the tag's category
* When viewing a parent image, a link to its children will be displayed and vice versa
* A page in the admin interface that allows for the deletion of aliases
* Child posts are no longer hidden by default
* Fixed the column headers for the alias page
* Fixed the `previous` link when viewing an image
* When logged in as an admin, searches will include a link at the bottom that will display every post's ID underneath its thumbnail. Clicking an ID will automatically highlight it and copy it to to your computer's clipboard
* Added `.webp` and `.svg` support
