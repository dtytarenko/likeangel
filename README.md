# LikeAngel WordPress Project

This repository contains the source code for the LikeAngel website, including the custom `woodmart-child` theme.

## Building the child theme

The child theme uses Gulp to compile SCSS and JavaScript assets. To build the assets:

```bash
cd wp-content/themes/woodmart-child
npm install
npm run build
```

The `dev` script will start BrowserSync using `localhost` for live reloading during development:

```bash
npm run dev
```

