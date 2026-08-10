# Using aliases within virtual links
## Explanation
If would like to have short or  other versions of your [virtual links](../core-features/parameter-abstraction.md), you can use aliases. To create a new alias, use the reroute method.  More about the reroute method can be found in the [API reference](../api/classes/ZubZet-Framework-Message-Response.html#method_reroute). Set the second parameter to true. This turns the reroute into an alias reroute. The current path will then be replaced with the first argument, but will not be truncated.

## Example
Only the first part of the URL get rerouted. The rest stays untouched.
### Code
`$res->reroute(["ws"], true);`
### Result
    Before:
    /workspace/upload/endpoint/
    
    After:
    /ws/upload/endpoint/