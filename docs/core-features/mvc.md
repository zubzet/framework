# Models, Views and Controllers (Structure)

A model-view-controller (MVC) is an architectural design pattern that organizes an application's logic into distinct layers, each of which carries out a specific set of tasks.

## **What's a model?**
The model depicts all interactions with your data structure. This is usually the database or a different data medium.
The model can be used to retrieve, update and remove data.

## **What's a controller?**
It handles all the sites logic. Routing is Based on the name of the controller and its actions. This Interacts with models and renders views.

## **What's a view?**
A view contains the html the user should see. All additional resources like css, images or javascript are also loaded from views. Views do usually **not** contain a footer, navigation, header or other elements that belong to the overall layout of the page. For this, layouts should be used as without a layout, a view can't be renderer. Read more about layouts [here](layouts.md).
