import { defineConfig } from '@playwright/test';
import base from './playwright.config';

// Preparation runs once, before discovery. Preserve its exact dynamic page
// manifest and authentication states during verification and shard execution.
export default defineConfig({ ...base, globalSetup: undefined });
