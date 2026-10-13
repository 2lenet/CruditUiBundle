import './bootstrap.js';
// The CSS of the bundle is imported explicitly, never by the autoimport of a controller: disabling a
// controller (the only way to override one with Encore) would remove its CSS.
import './styles/layers.css';
import '@2lenet/crudit-ui-bundle/dist/ui.css';
import './styles/app.css';
