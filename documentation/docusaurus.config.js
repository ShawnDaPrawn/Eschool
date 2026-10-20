// @ts-check
// `@type` JSDoc annotations allow editor autocompletion and type checking
// (when paired with `@ts-check`).
// There are various equivalent ways to declare your Docusaurus config.
// See: https://docusaurus.io/docs/api/docusaurus-config

import { themes as prismThemes } from 'prism-react-renderer';

// This runs in Node.js - Don't use client-side code here (browser APIs, JSX...)

/** @type {import('@docusaurus/types').Config} */
const config = {
  title: 'eSchool Documentation',
  tagline: 'Comprehensive documentation for eSchool covering installation, setup, features, and support.',
  favicon: 'images/logo/logo.png',

  // Set the production url of your site here
  url: 'https://shawndaprawn.github.io', // Your GitHub Pages URL
  baseUrl: '/Eschool/', // The repository name, preceded by a slash
  organizationName: 'ShawnDaPrawn', // Your GitHub username
  projectName: 'Eschool', // Your repository name
  trailingSlash: true,
  deploymentBranch: "gh-pages", // Deployment branch for GitHub Pages
  onBrokenLinks: 'throw',
  onBrokenMarkdownLinks: 'warn',

  // Even if you don't use internationalization, you can use this field to set
  // useful metadata like html lang. For example, if your site is Chinese, you
  // may want to replace "en" with "zh-Hans".
  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

  // Add local search plugin with a unique ID to avoid conflicts
  plugins: [
    [
      '@docusaurus/plugin-content-docs',
      {
        id: 'mobile-app',
        path: 'mobile-app', // Folder with markdown files
        routeBasePath: 'mobile-app', // URL path
        sidebarPath: require.resolve('./mobileAppSidebar.js'),
      },
    ],
    [
      '@docusaurus/plugin-content-docs',
      {
        id: 'admin-panel',
        path: 'admin-panel', // Another folder
        routeBasePath: 'admin-panel',
        sidebarPath: require.resolve('./adminPanelSidebar.js'),
      },
    ],
    [
      '@docusaurus/plugin-content-docs',
      {
        id: 'web-page',
        path: 'web-page', // Another folder
        routeBasePath: 'web-page',
        sidebarPath: require.resolve('./webPageSidebar.js'),
      },
    ],
    [
      '@docusaurus/plugin-content-docs',
      {
        id: 'features',
        path: 'features', // Another folder
        routeBasePath: 'features',
        sidebarPath: require.resolve('./featuresSidebar.js'),
      },
    ],
    [
      '@docusaurus/plugin-content-docs',
      {
        id: 'support',
        path: 'support',
        routeBasePath: 'support',
        sidebarItemsGenerator: async () => [],
      },
    ],
    [
      '@docusaurus/plugin-content-docs',
      {
        id: 'faqs',
        path: 'faqs',
        routeBasePath: 'faqs',
        sidebarItemsGenerator: async () => [],
      },
    ],
    [
      '@docusaurus/plugin-content-docs',
      {
        id: 'changelog',
        path: 'changelog',
        routeBasePath: 'changelog',
        sidebarItemsGenerator: async () => [],
      },
    ],
  ],

  presets: [
    [
      'classic',
      /** @type {import('@docusaurus/preset-classic').Options} */
      ({
        docs: false,
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
      }),
    ],
  ],

  themeConfig:
    /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
    ({
      // Replace with your project's social card
      image: 'images/logo/transparent_logo.svg',
      navbar: {
        logo: {
          alt: 'eSchool Logo',
          src: 'images/logo/transparent_logo.svg',
        },
        items: [
          {
            docsPluginId: 'mobile-app',
            type: 'docSidebar',
            sidebarId: 'mobileAppSidebar',
            position: 'left',
            label: 'Mobile App',
          },
          {
            docsPluginId: 'admin-panel',
            type: 'docSidebar',
            sidebarId: 'adminPanelSidebar',
            position: 'left',
            label: 'Admin Panel',
          },
          {
            docsPluginId: 'web-page',
            type: 'docSidebar',
            sidebarId: 'webPageSidebar',
            position: 'left',
            label: 'Web Pages',
          },
          {
            docsPluginId: 'features',
            type: 'docSidebar',
            sidebarId: 'featuresSidebar',
            position: 'left',
            label: 'Features',
          },
          {
            docId: 'index',
            docsPluginId: 'support',
            type: 'doc',
            position: 'left',
            label: 'Support',
          },
          {
            docId: 'index',
            docsPluginId: 'faqs',
            type: 'doc',
            position: 'left',
            label: 'FAQs',
          },
          {
            docId: 'index',
            docsPluginId: 'changelog',
            type: 'doc',
            position: 'left',
            label: 'Changelog',
          },
          {
            type: "search",
            position: "right",
          },
        ],
      },
      footer: {
        style: "dark",
        copyright: `Copyright © ${new Date().getFullYear()} Your Company. All rights reserved.`,
      },
      prism: {
        theme: prismThemes.github,
        darkTheme: prismThemes.dracula,
      },
      colorMode: {
        defaultMode: "light",
        disableSwitch: false,
        respectPrefersColorScheme: true,
      },
    }),
  themes: [
    [
      require.resolve("@easyops-cn/docusaurus-search-local"),

      ({
        hashed: true,
        docsRouteBasePath: ["mobile-app", "admin-panel", "web-page", "features"],
        docsDir: ["mobile-app", "admin-panel", "web-page", "features"],
        docsPluginIdForPreferredVersion: "mobile-app",
      }),
    ],
  ],

  // Add Font Awesome for icons
  stylesheets: [
    "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css",
  ],
};

export default config;
