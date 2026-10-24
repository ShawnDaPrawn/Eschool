// @ts-check
// `@type` JSDoc annotations allow editor autocompletion and type checking
// (when paired with `@ts-check`).
// There are various equivalent ways to declare your Docusaurus config.
// See: https://docusaurus.io/docs/api/docusaurus-config

import { themes as prismThemes } from 'prism-react-renderer';

// This runs in Node.js - Don't use client-side code here (browser APIs, JSX...)

/** @type {import('@docusaurus/types').Config} */
const config = {
  title: 'NavigateEd',
  tagline: 'Setup guides and everyday help for your connected school.',
  favicon: 'navigateed/student-app-icon.svg',
  // Publish the canonical brand assets without maintaining a second copy.
  staticDirectories: ['static', '../branding'],

  // Set the production url of your site here. DOCS_URL and DOCS_BASE_URL
  // override both for other hosts, e.g. Firebase Hosting serves from '/'.
  url: process.env.DOCS_URL || 'https://shawndaprawn.github.io', // Your GitHub Pages URL
  baseUrl: process.env.DOCS_BASE_URL || '/Eschool/', // The repository name, preceded by a slash
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
        sidebarPath: require.resolve('./sidebars/mobile-app.js'),
      },
    ],
    [
      '@docusaurus/plugin-content-docs',
      {
        id: 'admin-panel',
        path: 'admin-panel', // Another folder
        routeBasePath: 'admin-panel',
        sidebarPath: require.resolve('./sidebars/admin-panel.js'),
      },
    ],
    [
      '@docusaurus/plugin-content-docs',
      {
        id: 'web-page',
        path: 'web-page', // Another folder
        routeBasePath: 'web-page',
        sidebarPath: require.resolve('./sidebars/web-page.js'),
      },
    ],
    [
      '@docusaurus/plugin-content-docs',
      {
        id: 'features',
        path: 'features', // Another folder
        routeBasePath: 'features',
        sidebarPath: require.resolve('./sidebars/features.js'),
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
      image: 'navigateed/primary.svg',
      navbar: {
        logo: {
          alt: 'NavigateEd documentation home',
          src: 'navigateed/primary.svg',
          srcDark: 'navigateed/reversed.svg',
          width: 164,
          height: 48,
        },
        items: [
          {
            docsPluginId: 'mobile-app',
            type: 'docSidebar',
            sidebarId: 'mobileAppSidebar',
            position: 'left',
            label: 'Apps',
          },
          {
            docsPluginId: 'admin-panel',
            type: 'docSidebar',
            sidebarId: 'adminPanelSidebar',
            position: 'left',
            label: 'Admin panel',
          },
          {
            docsPluginId: 'web-page',
            type: 'docSidebar',
            sidebarId: 'webPageSidebar',
            position: 'left',
            label: 'Website',
          },
          {
            docsPluginId: 'features',
            type: 'docSidebar',
            sidebarId: 'featuresSidebar',
            position: 'left',
            label: 'Features',
          },
          {
            position: 'left',
            label: 'Resources',
            type: 'dropdown',
            items: [
              { label: 'Support', to: '/support/' },
              { label: 'FAQs', to: '/faqs/' },
              { label: 'Changelog', to: '/changelog/' },
            ],
          },
          {
            type: "search",
            position: "right",
          },
        ],
      },
      footer: {
        style: "dark",
        links: [
          { title: 'Get started', items: [
            { label: 'Admin panel', to: '/admin-panel/' },
            { label: 'Mobile apps', to: '/mobile-app/' },
            { label: 'School website', to: '/web-page/' },
          ] },
          { title: 'Explore', items: [
            { label: 'Feature guides', to: '/features/' },
            { label: 'What’s new', to: '/changelog/' },
          ] },
          { title: 'We’re here to help', items: [
            { label: 'Common questions', to: '/faqs/' },
            { label: 'Support', to: '/support/' },
          ] },
        ],
        copyright: `Copyright © ${new Date().getFullYear()} NavigateEd. All rights reserved.`,
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

};

export default config;
