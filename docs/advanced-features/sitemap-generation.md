# Using the Sitemap
## A description of the feature
Websites usually have some kind of sitemap. Basically, they are just a list of availible links on a website or webapp and make it easier for search engines to index all of the links, even those that are not linked to. 

## How to auto generate them
The framework is able to auto generate the sitemap. To open it, simply use `{root}/sitemap`. The default setting for any action is to not be displayed in the sitemap. You can either change the default setting by changing the key **sitemapPublicDefault** in the z_settings.ini file or by adding an attribute for each attribute foreach action you want displayed in the sitemap.

An attribute to enable the sitemap for an actions needs to look like the following:

| Access Modifyer | Storage Class  | $ and the action name   | The Setting | Value   |
| --------------- | -------------- | ----------------------- | ----------- | ------- |
| public          | static         | $action_test            | _sitemap    | = true; |

    Result:
    public static $action_test_sitemap = true;

If you are using `action_fallback`, you can also pass a string array instead of a simple true. Every entry in this array will than be interpreted as a unique link.