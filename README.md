PHP Provider Tutorial
=====================

This is a simple product provider built from the online guide.

This application uses [Slim][slim] for request handling and routing.

For simplicity application data is stored statically in the source, or temporarily using APCu.
Application data is reset whenever the application is restarted.

Requirements:

* PHP >= 8.0
* Slim (via Composer)
* APCu

Use the `run` script to start the application on http://localhost:3000/

[slim]: https://www.slimframework.com/
