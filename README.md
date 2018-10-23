The source code for version 0.1.9 of [Gelbooru](https://gelbooru.com) was publicly released in December 2008, but I found it was missing a few features present in the version used by the site itself and have added them here as well as some others that I have a need for. The only files in this repository are ones I created myself or ones from the version [0.1.11](https://gelbooru.com/index.php?page=forum&s=view&id=99&pid=0) package I have modified to expand gelbooru's feature set, though I might add the rest of the contents of that package to this repository in the future.

## Installation

Use of these features requires an already working installation of Gelbooru. Add the files from this repository to the top level of the Gelbooru installation directory (the directory structure is mimicked in this repository, so make sure to merge directories when copying) and execute the tag category upgrade script.

## Features

The major new features I've added are summarized below. The tag category feature is the only one that requires running the upgrade script, the rest will function by simply copying the repository's copy of the file to your gelbooru installation directory.

#### Tag Categories

(Requires running upgrade script)

Tags can now be assigned to a category, thereby setting them apart from generic tags when viewing posts. When viewing a single post, tags of the same category will be grouped together, seperately from uncategorized tags, and will be colored differently if outlined that way in the site's stylesheet. When viewing multiple posts, tags will __NOT__ be grouped together by category, but they will be colored appropriately.

Tag categories can be created and deleted via a page added to the admin interface, but the category's css style must be manually added to the stylesheet. The stylesheet in this repository includes the styles used on Gelbooru.com for the Artist, Copyright, and Character categories.

#### Recursive Image Import

The script `batch_add.php` from Gelbooru allows for the mass importing of large quantities of images that are stored on the server but have not been added to the database. The file in this repository `recursive_batch_add.php` is a fully recursive modification of that one. It searches the (configurable) directory `import` located in the same directory as the script and adds all images to the database. Every directory that the script encounters will have its name treated as a full tag and that tag will be added to every image that resides in that directory or any of its child directories.

#### Batch Tag Operations

Some pages have been added to the admin interface allowing tag operations to be done on large groups of posts. Operations include

* Adding or removing tags from multiple posts
* Replacing one tag with another in multiple posts
* Removing posts from the database based on their tags

Posts for these operations are selected by specifying IDs or evaluating a regular expression against a post's metadata (such as its tags, title, or source). Post IDs can be specified individually or as part of an inclusive range by inputting the two bounds of the range with a hyphen seperating them.

Example:
  489 732 590 400-450 789

#### Other

* A searchable tag listing page that also shows how many posts have each tag and the tag's category
* When viewing parent posts, a link to its children will be displayed and vice versa
* A page in the admin interface that allows for the deletion of aliases
* Search queries with no parent field no longer hide child posts
* Fixed the column headers for the alias page
* When logged in as an admin, searches will include a link at the bottom that will display every post's ID underneath its thumbnail. Clicking an ID will automatically highlight it and copy it to the OS's clipboard

## Todo

* Batch operation for changing tag categories
  * i.e. change all tags to generic where index_count < 50
* Sample image generation
* Search posts by other properties
  * title
  * comment count
  * score
* Default tags for new uploads
  * allow for user specific tags
* Parent and child tags
  * Adding a child tag to a post will automatically add its parent tag as well
* Tag pairs
  * Indicates a connection between two (or more) tags to further refine search capabilities without having to add millions of tags
  * Example: 
* Allow logical OR in searches
* Change `tag_ops.php` to not regex against image dimension. Probably going to have to add some extra fields to the form to allow them as search parameters.
* Make showing/hiding child posts in searches configurable

#### Bugs

* admin/alias.php
  * cannot approve aliases that contain a single quote (')
* classes/image.class.php
  * cannot create thumbnails for .bmp images
* misc
  * When deleting posts, set the parent field of any child posts to null
