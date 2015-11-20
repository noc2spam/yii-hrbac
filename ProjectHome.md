Introduction:
> HRBAC -> Hierarchical Role Based Access Control
> 'Hrbac' module is an enhancement of Yii RBAC feature.
  * It reduces the number of db queries when performing an access check
  * Add conditions (boolean tests) to auth assignments and auth relationships
> > Hopefully this is simpler than using bizrules and might help avoid
> > bizrules altogether (so no php needed in database).
  * It provides the UI to assign roles and manage the auth items.
  * Quick and easy setup of route access control. Do not need to modify any code
> > other than a couple lines in index.php and the config file main.php.

Using HRBAC : General
  * There are 3 types of authorization items. Roles, Operations or Actions, and
> > Tasks (or Groups of Operations). Roles may contain other auth items and Tasks
> > or Op Groups may contain other groups or ops. You can't create a circular
> > association though.
  * You may assign any number of auth items to  a user. It can be any of the
> > three types but you would mostly want to assign roles.
  * Any assignment can be made conditional by specifying some conditions or
> > 'bizrules' to the assignment. Note that this condition or rule pertains
> > only to that particular assignment. We can have conditions and bizrules
> > that apply to specific auth items too but more of that later.
  * 'Conditions' are sets of boolean tests to determine if the role applies
> > for that access test.  It uses a simple syntax (more later).
  * 'Bizrule' is a PHP code that's saved with the assignment in the database
> > and is 'eval'ed to determine if the assignment applies. The bizrule must
> > evaluate to true or false. Personal thought: it's probably not a good idea
> > to use bizrules if it can be avoided.
  * Each auth item may also have a condition and/or a bizrule associated with it.
  * The benefit of using conditions or bizrules is that the authorization is
> > effective only when certain conditions are met. For example, you can set it
> > up so that it is effective only for a month, or only for certain sections of
> > your website and so on.
  * Each user also automatically gets assigned one of these two roles:
> > 'Anonymous User' or 'Authenticated User' depending on whether they are logged
> > in or not.
  * Each controller may also assign transient roles based on the target object, such
> > as 'Author', 'Owner', 'Manager'.
  * Hrbac also does route based authorization. So if a user has access to a particular
> > controller e.g. /module\_id/controller\_id/, he also has access to all the actions
> > within that controller. If another user has access to the module e.g. /module\_id/
> > he has access to all controllers and actions within that module.

Requirement:
  * A database table with the name 'users' with a primary key column 'id' and a column
> > 'username'.
> > a. If the username column is named differently, you'd have to do some search
> > > and replace :(

> > b. If the table name is different, just open HrbacModule.php and change it there.