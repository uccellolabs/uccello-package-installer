# Package installer for Uccello

This package allows you to install new composer packages directly from the Uccello interface.

It uses the PHP class [ZipArchive](https://www.php.net/manual/fr/class.ziparchive.php) and requires the PECL zip extension >= 1.1.0.

## Installation

```
composer install uccello/package-installer
```

## Add the form in a page

It is possible to easily add the package upload form in the page of your choice.

**Warning:** The user must have `admin` rights on the `package-install` module to be able to see the form and execute the installation.

You can add the following code in a Blade page:

```
@include('package-installer::partials.package_upload_form')
```

## Security

If you discover any security related issues, please email [jonathan@uccellolabs.com](mailto:jonathan@uccellolabs.com) instead of using the issue tracker.

## Credits

- [Uccello Labs](https://github.com/uccellolabs)
- [Jonathan SARDO](https://github.com/sardoj)
- [All Contributors](https://github.com/uccellolabs/uccello/contributors)

## License

This packcage is under MIT License (MIT).
