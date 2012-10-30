CakeDeploy
==========

---

> A plugin that helps to Deploy a CakePHP Application

---

## What CakeDeploy does?

Pretend **your CakePHP project** lies into that folder:

`/Users/peg/Sites/my-project/`

Now you can **run CakeDeploy** by entring this url in you browser:

`http://localohost/my-project/cake_deploy`

A **new project folder is created** in:

`/Users/peg/Sites/my-project-deploy/`


## Just clone my project's folder?

No! A full recursive copy is done following some rules like:

- skip some files or folders
- follow symlink (build linked libraries)
- skip copy unchanged files
- [optional] strip comments from php files
- [optional] uglify php fiels

You deployed folder is ready to be uploaded with FileZilla with the ability to skip unchanged files!

## How does it works?

CakeDeploy uses PhpCompiler, a deploy utility created by [@MovableAPP](http://twitter.com/movableapp):  
http://movableapp.com/2012/10/php-compiler-deployment-utility

Please follow instruction on how to configurate your deployment plugin on that page!


## Copyright

Copyright (c) 2012 Marco Pegoraro - marco(dot)pegoraro(at)gmail(dot)com - MovableAPP.com

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.