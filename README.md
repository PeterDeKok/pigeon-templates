# Pigeon Templates for Laravel

Templating wrapper using a hierarchical recursive concept. 
Laravel's (Eloquent) Model relations are used to recursively retrieve templates. 
If the current model does not have a template for a template part, the parent model is tried. 

A directive is used to include (nested) template parts. 
This approach limits duplication in templates. 
All boilerplate only has to be stored once. 
And only the varying parts have multiple entries.

Supports at least Laravel 5.6, untested for any other versions. 

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Support](#support)
- [Contributing](#feature-requests--contributions)

## Installation

Use composer to add this package to your project:

```sh
composer require peterdekok/pigeon-templates
```

This should also auto-discover the PigeonTemplatesServiceProvider

If any customisation is needed (extremely likely) run one or more of the following commands:
```sh
php artisan vendor:publish --provider PeterDeKok\PigeonTemplates\PigeonTemplatesServiceProvider --tag pigeon-templates-config
php artisan vendor:publish --provider PeterDeKok\PigeonTemplates\PigeonTemplatesServiceProvider --tag pigeon-templates-views
php artisan vendor:publish --provider PeterDeKok\PigeonTemplates\PigeonTemplatesServiceProvider --tag pigeon-templates-migrations
```

## Configuration

To explain the rest of the configuration I will use two models with two (base) templates.

- A company model, which has many users
- A PDF template and an e-mail template

To add templates to a Model, it has to implement the PigeonTemplateContract. 
This can be easily done by adding the HasPigeonTemplates trait.
(Don't forget to add the interface as well).

This will also force you to configure the pecking order (or hierarchy) of your models. 

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use PeterDeKok\PigeonTemplates\HasPigeonTemplates;
use PeterDeKok\PigeonTemplates\PigeonTemplatesContract;

/**
 * Class User
 * 
 * @property-read \App\Models\Company $company
 * @mixin \Eloquent
 */
class User extends Model implements PigeonTemplatesContract {
    
    use HasPigeonTemplates;
    
    public function company() {
        return $this->belongsTo(Company::class);
    }
    
    /**
     * @return \PeterDeKok\PigeonTemplates\PigeonTemplatesContract|null
     */
    public function pigeonParent() {
        // The model behind this relationship should als implement the PigeonTemplatesContract
        return $this->company;
    }
}

/**
 * Class Company
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @mixin \Eloquent
 */
class Company extends Model implements PigeonTemplatesContract {
    
    use HasPigeonTemplates;
    
    public function users(){
        return $this->hasMany(User::class);
    }
    
    /**
     * @return \PeterDeKok\PigeonTemplates\PigeonTemplatesContract|null
     */
    public function pigeonParent() {
        // Top level model.
        return null;
    }
}
```

#### Content types

- view
    - A blade file (by default in the pigeon-templates namespace).
    - Requires views to be published to be modified.
    - Stored as the view hint in the database. 
- blade
    - Blade syntax, but stored in the database (or default config).
    - Will be parsed as a view.
- html
    - A 'simple' html string that will not be parsed as a view.
- image-url
    - An image retrieved from any URI accessible by whatever renders the HTML.
- data-image
    - A base64 string representing an image in a format the HTML engine can understand.
    - Including `data:image/png;base64,...`.
    
#### Template parts

The view and blade content types can contain nested template parts. 
For example the base template part can be the same for all users within a company, 
but it may contain a custom profile picture for each user.

```blade
<!-- base.blade.php -->

<!DOCTYPE html>
<html>
    <head></head>
    <body>
        <div>
            <div class="profile">
                @template("profilepicture")
            </div>
            
            <div>
                <h1>{{ $user->name }}</h1>
                <p>Lorem Ipsum...</p>
            </div>
        </div>
    </body>
</html>
```

This is a very boring example of course, but you can see where this is going. 
For example: 
- You could change the CSS for all users of one Company only and keep the rest default.
- You could add an extra disclaimer to the footer of the PDF for a couple of users.
- You could throw in some server statistics only for you as an admin.

The `@template("templatepart")` directive holds the magic so to speak. 
The `templatepart` parameter should be one of the predefined template parts in the config.

This list in the config contains the names of the template parts with their corresponding content-types and a default value.
The default value can be as simple as an empty string, but I highly recommend setting a sensible default. 
If any of the top level models do not have a template linked, only an empty string would be rendered 
(if ignore-errors is set to true).

> #### Note
> Note that the blade content type uses the blade engine to process the syntax (including resolving php code)
> and the syntax is stored in the database. This COULD open you up to a security risk!
> Make sure if you have a blade content type in your config, you know where the data (syntax) is coming from. 

## Usage

When the Template parts and some sensible defaults have been set, you can start rendering.

Well ... full disclosure ... you could skip the configuration and start right away. 
The result would only be boring examples though.

So, this is how you do it:

```php
$rendered = PeterDeKok\PigeonTemplates\PigeonTemplateRenderer::render($user, compact('user'), 'base');
```

Or through the User Model:

```php
$rendered = $user->renderPigeon(compact('user'), 'base');
```

You can also set a default templateType in the (published) config so you can call the render method a little easier.

```php
$rendered = $user->renderPigeon(compact('user'));
```

Or even without data. 

```php
$rendered = $user->renderPigeon();
```

> #### Note
> This will automatically add the model (User in this case) to the data.
> This is not done through the compact method.
> 
> The key (and thus the variable in blade) of this data is the singular name of the table associated with the model.

> #### Note
> If you DON'T want anything as data, you can pass an empty array. 
>
> Or if you DON'T want all model attributes, process the data and pass it to the method yourself. 

## Support

Please [open an issue](https://github.com/PeterDeKok/pigeon-templates/issues/new/choose) for support.

## Feature Requests & Contributions

I will accept feature requests. please do so by [opening an issue](https://github.com/PeterDeKok/pigeon-templates/issues/new/choose).

Depending on the number of feature requests and bug reports I might not be able to spend time on this issue 
(at least in a timely fashion). Also a life outside of templates will take some time away. 

Another option is to send me a pull-request. I recommend creating a feature request first though, 
so the intended fix/feature can be discussed first. 

I will definitely be open to collaborators, but I reserve the right to refuse pull-requests 
if they lead me in a different path then intended.

Please contribute using [Github Flow](https://guides.github.com/introduction/flow/). 

 - [ ] Clone the repo 
 - [ ] create a new branch (starting from the current develop branch)
 - [ ] add commits
 - [ ] [open a pull request](https://github.com/peterdekok/pigeon-templates/compare/)
