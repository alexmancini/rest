# Javascript REST Client

The Javascript REST client is an ActiveRecord style API for working with REST 
services. It is built on top of jQuery and is easy to use.

All you need to do is make sure you require jQuery and jActiveResource:

    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.js"></script>
    <script src="jActiveResource.js"></script>

Now you can get started by defining new entity:

    jActiveResource.define('User', {
      url: 'http://localhost/rest/server.php/user',

      username: null,
      password: null,

      toString: function () {
        return 'username=' + this.username + '&password=' + this.password;
      }
    });

You can start creating instances and saving them:

    var user = User.create();
    user.username = 'jwage';
    user.password = 'password';

    // POST http://localhost/rest/server.php/user.json
    user.save(function (user) {
      alert(user.username + ' saved!');
    });

You can easily retrieve all the users with findAll():

    var users = User.findAll(null, function(users) {
      alert(users.length + ' users returned');
    });

If you want to retrieve a single User you can use the find() method:

    var user = User.find(1, function(user) {
      user.username = 'jon';
      user.save(function (user) {
        alert(user.id + ' updated');
      });
    });