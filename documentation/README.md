# Documentation

Documentation site for this repository. Page content is grouped by area (`mobile-app/`, `admin-panel/`, `web-page/`, `features/`, `support/`, `faqs/`, `changelog/`). Sidebar configuration is in `sidebars/`.

Follow these steps to run the site locally.

---

## 🚀 Prerequisites

Before you begin, make sure you have the following installed:

- Node.js (version **20** or higher)

---

## 🛠️ Installation Steps

### 1. Install Node.js  
Download and install Node.js from the official website:  
👉 [https://nodejs.org/](https://nodejs.org/)  
> ⚠️ Make sure you’re using **Node.js v20.x.x or higher**

---

### 2. Open the Project in a Code Editor  
Use any modern code editor like **Visual Studio Code** to open the project folder.

---

### 3. Verify Node.js Installation  
Open your terminal and run the following command:

```bash
node -v
```

You should see a version number that starts with `20.` or higher.

---

### 4. Install Dependencies  
Navigate to the root of the project folder in the terminal and run:

```bash
npm i
```

This will install all required packages and dependencies.

---

### 5. Start the Development Server  
Once all dependencies are installed, run:

```bash
npm run start
```

This will compile and launch the documentation site locally.

---

### 6. View in Browser  
After the server starts, it will typically open automatically in your default browser.  
If it doesn’t, you can open the following link manually:

```
http://localhost:3000
```


## NavigateEd UI and branding

The documentation uses the same Material 3 design system as the apps and panel.
`src/css/custom.css` imports the generated `admin-panel/public/assets/css/m3-tokens.css`
and the web component library in `admin-panel/public/assets/m3/m3.css`, including
its local Poppins fonts. Update the shared theme and regenerate its tokens when
changing the brand; do not add a separate documentation colour palette.

Docusaurus publishes the canonical `branding/` folder as a static directory.
The navbar uses `navigateed/primary.svg` on light surfaces and
`navigateed/reversed.svg` on dark surfaces. No copied logos or remote fonts are
needed. Build from a full repository checkout so these shared files are present.

Check the homepage, an article, search, keyboard focus and the mobile menu in
both light and dark mode before publishing. Respect reduced-motion preferences.
Existing guide URLs remain unchanged.

To build for Firebase Hosting (served from `/`):

```bash
GCLOUD_PROJECT=navigateed-d8c06 npm run build:firebase
```

The default `npm run build` continues to build for GitHub Pages at `/Eschool/`.


## Firebase previews and publishing

The `Firebase documentation` workflow uses the repository Actions secret
`FIREBASE_SERVICE_ACCOUNT_NAVIGATEED_D8C06`, containing the complete JSON key of
`navigateed-docs-deployer@navigateed-d8c06.iam.gserviceaccount.com`. The workflow
checks the credential identity without printing it, builds for the Firebase root
path, and runs `scripts/check_ui.py` before deploying Hosting. It does not deploy
the Laravel backend or touch school data.

Same-repository documentation PRs publish the `docs-ui-review` preview channel
(expiring after seven days). The workflow run records its URL and retains browser
screenshots as an artifact. After preview acceptance and merge, run the workflow
from `main` with `channel=live` to publish https://navigateed-d8c06.web.app/.
Live runs use the `production` GitHub environment and respect its reviewer rules.

Browser checks cover 320, 390, 1024 and 1440 px layouts in light and dark mode,
loaded fonts and logos, the mobile menu, theme control, guide links and search.
For a local Firebase build served on port 3010, install Python Playwright and its
Chromium browser and run `python scripts/check_ui.py`. Set `DOCS_TEST_URL` for
another serving URL, or `DOCS_CHROMIUM_EXECUTABLE` to use a system Chromium.
The Poppins license is published at `licenses/poppins-OFL.txt`.

Both production build commands clear Docusaurus caches before compiling. This
prevents font and asset URLs from retaining the previous target base path when
switching between GitHub Pages (`/Eschool/`) and Firebase (`/`).
