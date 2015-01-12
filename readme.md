#Page list snippet#

A [PhileCMS](https://github.com/PhileCMS/Phile) plugin that allows you to list or search on pages
on your site. You can make a list of all pages, of pages below the current or below a certain other
page, of pages matching a specific query (full text search!), and you can filter, sort and provide
custom templates for your page list.

This plugin works with the default Markdown parser, but it should also work with any of the other
plugins that offer Markdown alternatives. It should even work if applied to regular HTML files.

##Installation##

###With composer###

    php composer.phar require infostreams/pagelistSnippet

###Download###

* Install [Phile](https://github.com/PhileCMS/Phile)
* Clone this repo into plugins/infostreams/pagelistSnippet


###Activation###

After you have installed the plugin. You need to add the following line to your config.php file:

    $config['plugins']['infostreams\\pagelistSnippet'] = array('active' => true);

You need to have the [Snippets](https://github.com/infostreams/snippets) plugin installed and
activated for this to work.

##How it works##
Once you have installed this plugin, you can include a list of pages anywhere in your site by
typing the following code into your Markdown page:

    (pagelist: all)

This would include a list of all pages on your site, using the default template.

###Templates###
Since the default template is rather bare bones, you probably want to provide your own template.

    (pagelist: all template:elements/pagelist)

This would style the list of pages with a custom template, provided by you. This template is much
like a regular (Twig) template, but it's a **partial** template, which means it will only be used
for a part of your page. In this template you can access the list of pages by using the `pagelist`
variable. For an example, have a look at the `plugins/infostreams/Templates/pagelist-default.html`
file.

If you want to override the default template, you can create a template called
`pagelist-default.html` in your own theme. That will override the default template that comes with
a fresh install of the PagelistSnippet plugin, and it can be used to provide a standardized list
template for your whole site.

##Available options##

The first parameter can be either `all`, `below`, or `search`, or it can contain a named list of
pages.

###'all'###
List all the pages

    (pagelist: all)

###'below'###
List all pages below the *current* page

    (pagelist: below)

List all pages below *another* page

    (pagelist: below under: company/team)

List all pages below another page, and include that other page itself as well:

    (pagelist: below under: company/team inclusive:true)


###'search'###
List all pages containing the text provided in the URL (by default, in the `q` parameter):

    (pagelist: search)

The search results are ordered by relevancy according to the
[TF-IDF](https://en.wikipedia.org/wiki/Tf%E2%80%93idf) metric.

List all pages containing the text provided in the URL, but change the parameter name in which
this is provided to 'search'

    (pagelist: search param:search)

This would make the search respond to URLs in the form `....?search=keyword` instead of the
default `...?q=keyword`.

Please be aware that the mentioned TF-IDF metric is relatively simple. This is not Google.

###Named pages###
You can manually list the pages you want to include in your page list:

    (pagelist: [company/team/ceo, company/team/cto])

##Filtering and sorting##
You can filter the page list based on information in the metadata. Filters are specified as an array,
i.e. between [brackets], and support regular expressions by default.

Display all pages below the current page that have the `project` template:

    (pagelist: below filter:[template:project])

Display all pages below the current page that have a template whose name matches the mentioned
regular expression:

    (pagelist: below filter:[template:content.*])

You can sort the resulting pages by providing an `order` parameter. The syntax for ordering
pages follows PhileCMS's syntax.

    (pagelist: below
        under: company/team
        order: meta.surname:asc)

List all team members, order them by surname, and display using a custom template:

    (pagelist: below
        under: company/team
        order: meta.surname:asc
        template: team-members)

This would require you to add a file named `team-members.html` to your theme.

You can hard code a full-text keyword search if you want to:

    (pagelist: all
      keyword: sales)

This would list all pages that contain the keyword 'sales'.

##Combining##
You can combine almost all the parameters listed above:

    (pagelist: below
        under: company/team
        order: meta.surname:asc
        template: team-members
        filter: [tag: helpdesk]
        keyword: europe
    )

This would list all the pages below 'company/team' that have the 'helpdesk' tag and contain the
keyword 'europe', order them by surname and render them with the 'team-members.html' template.


###Known limitations###
I **suspect** that the current implementation only works with Twig templates. I haven't tested it
with other templating plugins. If you managed to get it working with other plugins than Twig,
drop me a line or open a pull request.