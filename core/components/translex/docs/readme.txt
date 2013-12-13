--------------------
TransleX - 1.0
--------------------

First Released: April 21st, 2012
Author: Joe Molloy <info@hyper-typer.com>
License: GNU GPLv2 (or later at your option)

This addon is a translation tool, enabling developers and site administrators to give users (annonymous or registered)
access to a simple AJAX form-based interface for making contributions to 3rd party package lexicons. Packages, topics 
and languages can be restricted by the site administrator/developer and the site administrator may also replace the 
current live topic file with one updated using this addon.  The addon creates a timestamped backup of any topic files 
replaced in this way.  

TransleX has been developed for Mr Mark Hamstra who also concieved the idea (http://www.markhamstra.com) and 
he has generously released it as open source (GPL v2+) to benefit others with similar needs.


Instructions
------------
1. Install via package manager
2. Create a resource where you want to display the form, setting its template to blank and disabling the Rich Text Editor 
and caching.  You should protect this page by adding it to a resource group accessible by the user(s) you which to give 
access to.
3. In the content field of the resource you created, simply call the snippet - [[!transleX]]
4. Read on for further configuration.


Usage
------------
By default, all packages within the components directory will be displayed - to restrict access to one or more packages 
simply add a 'packages' property with a comma delimited list of packages as its value these package names should be taken 
from the package's corresponding folder in the components directory.  For example to restrict access
to the transleX package itself you would have [[!transleX? &packages=`translex`]] while to restrict access to the 
packages formit and translex you would have [[!transleX? &packages=`formit,translex`]].

Similarly, to restrict the topic files a user may create or edit, you simply include a topics property with a comma dlimited 
list of topics you wish users to have access to.  For example [[!transleX? &packages=`formit` &topics=`default`]] would 
restrict users to contributing to the default topic file for formit package.

The same holds true for languages, to restrict users to a particular language, add a languages property to your snippet call,
this time with a comma delimited list of two letter language codes.  For example 
[[!transleX? &packages=`formit` &topics=`default` &languages=`en,fr`]] would restrict users to contributing to the default topic 
files for English and French in the formit package.

When a topic file is saved, it is saved to a 'workspace' directory in the translex directory in the site's components directory 
under the chosen package name and language directories.

The site administrator (user with id=1) has the extra option of committing an updated topic file to the appropriate location in the 
actual package lexicon, essentially making that topic file 'live' - the one which MODX uses.  You will need to clear the site 
cache for the new topic file to take effect.  Just click the 'Make Live' button.

In addition, you may configure the snippet call to send an email alert to a specified email address when a topic file is saved using 
the 'adminNotify' property in your snippet call.

Finally, you can also log activities to a dedicated tranlex.log file stored in the TransleX workspace directory.  You can opt to record 
any of the following events, tool access including the user's full name, email and their IP, topic file saves and errors relating to the
operation of the tool.  For more information, see the log property below.  This log can be viewed and managed through the tool's interface 
when used under the site adminstrator account, i.e. user_id = 1.

Properties:
packages:		A comma delimited list of the package names you wish the translator to work with.  These names are the same as the folder names 
				in the core/components directory.
				By default, all packages in the directory will be available.
topics:			A comma delimited list of the topic names you wish the translator to work with.  These names are the same as the topic file names 
				but without the .inc.php ending. So for example, to restrict a user to editing the default topic (default.inc.php) you would use 
				&topics=`default` in your snippet call.
				By default, all topic files for a given package will be available.
langauages: 	A comma delimited list of the languages you wish the translator to work with.  These languages are given in 2 letter code form.
				By default, the translator may work with all languages available.
adminNotify:	An email address to send notifications to when a topic file is saved.  Only one email will be sent per package, topic, language 
				combination per session.
				By default, no notification is sent.
cultureKey:		You can override the site cultureKey setting to have the tool interface display in another language of your choice.  Once again, 
				use the standard two letter code to specify the language to use.  You should also note that this will change what TransleX 
				considers the default langauge for each package.
				By default, the culture key used will be the one for the site - by default 'en'.
log: 			A comma delimited list of events you would like to log.  Valid options are 'error', 'save', 'commit' and 'access' or any combination 
				of these.  The log file is stored in the TransleX workspace folder.  If the log property is set, when an administrator visits the tool, 
				they can view and clear the log file from within the tool.
				By default, no logging is enabled.


You may also read this documentation at:
http://hyper-typer.com/news/modx-lexicon-translation-tool-translex


Licensing
------------
This addon is licensed under the GPL Public License Version 2 (or higher) and you should have received a copy of same along 
with this addon.

Thanks for using TransleX!
Joe Molloy
info@hyper-typer.com
