import React, { useEffect, useRef } from 'react';
import clsx from "clsx";
import Link from "@docusaurus/Link";
import useDocusaurusContext from "@docusaurus/useDocusaurusContext";
import Layout from "@theme/Layout";
import styles from "./index.module.css";
import logo from "../../static/images/logo/logo.png";
import DocBanner from '../components/DocBanner/DocBanner';

function HomepageHeader() {
  const { siteConfig } = useDocusaurusContext();
  const headerRef = useRef(null);
  const lastMousePos = useRef({ x: 0, y: 0 });
  const velocity = useRef({ x: 0, y: 0 });

  useEffect(() => {
    const header = headerRef.current;
    if (!header) return;

    const createIcon = (x, y, velocityX = 0, velocityY = 0) => {
      const el = document.createElement("div");
      el.className = styles.cursorIcon;
      const randomIcon = Math.floor(Math.random() * 3); // 0: book, 1: pencil, 2: graduation
      const size = Math.random() * 6 + 14; // Random size between 14-20px

      el.innerHTML = `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="${getIconPath(randomIcon)}" stroke="currentColor" stroke-width="1.2" fill="none"/>
      </svg>`;

      // Calculate position with velocity influence
      const angle = Math.random() * Math.PI * 2;
      const distance = Math.random() * 20 + 5; // Closer to cursor (5-25px)
      const offsetX = Math.cos(angle) * distance;
      const offsetY = Math.sin(angle) * distance;

      el.style.left = `${x + offsetX}px`;
      el.style.top = `${y + offsetY}px`;
      el.style.position = 'fixed';
      el.style.pointerEvents = 'none';
      el.style.color = 'rgba(255, 255, 255, 0.85)';
      el.style.transform = 'scale(0) rotate(0deg)';
      el.style.transition = 'all 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
      el.style.filter = 'blur(0px)';
      el.style.opacity = '0';

      document.body.appendChild(el);

      // Calculate random rotation and movement
      const rotation = (Math.random() - 0.5) * 180; // Less rotation
      const moveX = (Math.random() - 0.5) * 60 + velocityX; // Less spread
      const moveY = -Math.random() * 60 - 30 + velocityY; // Less vertical movement

      // Trigger animation
      requestAnimationFrame(() => {
        el.style.transform = `scale(1) rotate(${rotation}deg) translate(${moveX}px, ${moveY}px)`;
        el.style.opacity = '1';
        el.style.filter = 'blur(0.5px)'; // Less blur
      });

      // Remove after animation
      setTimeout(() => {
        el.remove();
      }, 800);
    };

    const handleMouseMove = (e) => {
      // Calculate velocity
      const currentX = e.clientX;
      const currentY = e.clientY;
      velocity.current = {
        x: currentX - lastMousePos.current.x,
        y: currentY - lastMousePos.current.y
      };
      lastMousePos.current = { x: currentX, y: currentY };

      // Create icons based on velocity
      const speed = Math.sqrt(velocity.current.x ** 2 + velocity.current.y ** 2);
      const numIcons = Math.min(Math.floor(speed / 8), 3); // Fewer icons, higher speed threshold

      for (let i = 0; i < numIcons; i++) {
        setTimeout(() => {
          createIcon(currentX, currentY, velocity.current.x, velocity.current.y);
        }, i * 40);
      }
    };

    const handleMouseEnter = (e) => {
      // Create initial burst of icons
      for (let i = 0; i < 8; i++) { // Fewer initial icons
        setTimeout(() => {
          createIcon(e.clientX, e.clientY);
        }, i * 40);
      }
    };

    header.addEventListener("mousemove", handleMouseMove);
    header.addEventListener("mouseenter", handleMouseEnter);

    return () => {
      header.removeEventListener("mousemove", handleMouseMove);
      header.removeEventListener("mouseenter", handleMouseEnter);
    };
  }, []);

  // Helper function to get SVG path data
  const getIconPath = (idx) => {
    switch (idx) {
      case 0: // book
        return "M19.8978 16H7.89778C6.96781 16 6.50282 16 6.12132 16.1022C5.08604 16.3796 4.2774 17.1883 4 18.2235 M8 7H16 M8 10.5H13 M10 22C7.17157 22 5.75736 22 4.87868 21.1213C4 20.2426 4 18.8284 4 16V8C4 5.17157 4 3.75736 4.87868 2.87868C5.75736 2 7.17157 2 10 2H14C16.8284 2 18.2426 2 19.1213 2.87868C20 3.75736 20 5.17157 20 8M14 22C16.8284 22 18.2426 22 19.1213 21.1213C20 20.2426 20 18.8284 20 16V12";
      case 1: // pencil
        return "M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z";
      case 2: // graduation
        return "M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z";
      default:
        return "";
    }
  };

  return (
    <header ref={headerRef} className={clsx("hero hero--primary", styles.heroBanner)}>
      <div className="container">
        <div className={styles.logoContainer}>
          <img
            src={logo}
            alt="eSchool Logo"
            width="120"
            height="120"
            className={styles.logo}
          />
        </div>
        <h1 className="hero__title">{siteConfig.title}</h1>
        <p className="hero__subtitle">{siteConfig.tagline}</p>
        <div className={styles.buttons}>
          <Link
            className="button button--secondary button--lg"
            to="/mobile-app/"
          >
            Get Started
          </Link>
        </div>
      </div>
    </header>
  );
}


function HomepageFeatures() {
  return (
    <section className={styles.features}>
      <div className="container">
        <div className="row">
          <div className="col col--3">
            <div className="card margin-bottom--lg">
              <div className="card__header">
                <div className={styles.cardIcon}>
                  {/* Mobile App Icon */}
                  <svg width="32px" height="32px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 18H12.01M11 6H13M9.2 21H14.8C15.9201 21 16.4802 21 16.908 20.782C17.2843 20.5903 17.5903 20.2843 17.782 19.908C18 19.4802 18 18.9201 18 17.8V6.2C18 5.0799 18 4.51984 17.782 4.09202C17.5903 3.71569 17.2843 3.40973 16.908 3.21799C16.4802 3 15.9201 3 14.8 3H9.2C8.0799 3 7.51984 3 7.09202 3.21799C6.71569 3.40973 6.40973 3.71569 6.21799 4.09202C6 4.51984 6 5.07989 6 6.2V17.8C6 18.9201 6 19.4802 6.21799 19.908C6.40973 20.2843 6.71569 20.5903 7.09202 20.782C7.51984 21 8.07989 21 9.2 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </div>
                <h3>Mobile Application</h3>
              </div>
              <div className="card__body">
                <p>
                  Comprehensive step-by-step guide to setup both Flutter application codes with detailed configuration instructions.
                </p>
              </div>
              <div className="card__footer">
                <Link
                  className="button button--outline button--primary"
                  to="/mobile-app/"
                >
                  Mobile App Setup
                </Link>
              </div>
            </div>
          </div>
          <div className="col col--3">
            <div className="card margin-bottom--lg">
              <div className="card__header">
                <div className={styles.cardIcon}>
                  {/* Admin Panel Icon */}
                  <svg fill="currentColor" width="32px" height="32px" viewBox="0 0 1920 1920" xmlns="http://www.w3.org/2000/svg">
                    <path d="M276.941 440.584v565.722c0 422.4 374.174 625.468 674.71 788.668l8.02 4.292 8.131-4.292c300.537-163.2 674.71-366.268 674.71-788.668V440.584l-682.84-321.657L276.94 440.584Zm682.73 1479.529c-9.262 0-18.523-2.372-26.993-6.89l-34.9-18.974C588.095 1726.08 164 1495.906 164 1006.306V404.78c0-21.91 12.65-41.788 32.414-51.162L935.727 5.42c15.134-7.228 32.866-7.228 48 0l739.313 348.2c19.765 9.374 32.414 29.252 32.414 51.162v601.525c0 489.6-424.207 719.774-733.779 887.943l-34.899 18.975c-8.47 4.517-17.731 6.889-27.105 6.889Zm467.158-547.652h-313.412l-91.595-91.482v-83.803H905.041v-116.78h-83.69l-58.503-58.504c-1.92.113-3.84.113-5.76.113-176.075 0-319.285-143.21-319.285-319.285 0-176.075 143.21-319.398 319.285-319.398 176.075 0 319.285 143.323 319.285 319.398 0 1.92 0 3.84-.113 5.647l350.57 350.682v313.412Zm-266.654-112.941h153.713v-153.713L958.462 750.155l3.953-37.27c1.017-123.897-91.595-216.621-205.327-216.621S550.744 588.988 550.744 702.72c0 113.845 92.612 206.344 206.344 206.344l47.21-5.309 63.811 63.7h149.873v116.78h116.781v149.986l25.412 25.299Zm-313.4-553.57c0 46.758-37.949 84.706-84.706 84.706-46.758 0-84.706-37.948-84.706-84.706s37.948-84.706 84.706-84.706c46.757 0 84.706 37.948 84.706 84.706" fill-rule="evenodd" />
                  </svg>
                </div>
                <h3>Admin Panel</h3>
              </div>
              <div className="card__body">
                <p>
                  Complete backend code installation guide and admin panel setup instructions
                  for managing the entire eSchool ecosystem efficiently.
                </p>
              </div>
              <div className="card__footer">
                <Link
                  className="button button--outline button--primary"
                  to="/admin-panel/"
                >
                  Admin Panel Setup
                </Link>
              </div>
            </div>
          </div>
          <div className="col col--3">
            <div className="card margin-bottom--lg">
              <div className="card__header">
                <div className={styles.cardIcon}>
                  {/* Web Pages Icon */}
                  <svg fill="currentColor" width="32px" height="32px" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg">
                    <path d="M32.032 16c0-8.501-6.677-15.472-15.072-15.969-0.173-0.019-0.346-0.032-0.523-0.032-0.052 0-0.104 0.005-0.156 0.007-0.093-0.002-0.186-0.007-0.281-0.007-8.84 0-16.032 7.178-16.032 16.001s7.192 16.001 16.032 16.001c0.094 0 0.188-0.006 0.281-0.008 0.052 0.002 0.104 0.008 0.156 0.008 0.176 0 0.349-0.012 0.523-0.032 8.395-0.497 15.072-7.468 15.072-15.969zM29.049 21.151c-0.551-0.16-1.935-0.507-4.377-0.794 0.202-1.381 0.313-2.84 0.313-4.357 0-1.196-0.069-2.354-0.197-3.469 3.094-0.37 4.45-0.835 4.54-0.867l-0.372-1.050c0.695 1.659 1.080 3.478 1.080 5.386 0 1.818-0.352 3.555-0.987 5.151zM8.921 16c0-1.119 0.074-2.212 0.21-3.263 1.621 0.127 3.561 0.222 5.839 0.243v6.939c-2.219 0.021-4.114 0.111-5.709 0.234-0.22-1.319-0.34-2.715-0.34-4.154zM16.967 2.132c2.452 0.711 4.552 4.115 5.492 8.628-1.512 0.12-3.332 0.209-5.492 0.229v-8.857zM14.971 2.156v8.832c-2.136-0.021-3.965-0.109-5.502-0.226 0.96-4.457 3.076-7.836 5.502-8.606zM14.971 21.913l0 7.929c-2.263-0.718-4.256-3.705-5.293-7.719 1.492-0.11 3.253-0.189 5.292-0.21zM16.967 29.868l-0-7.955c2.061 0.020 3.814 0.102 5.288 0.217-1.019 4.067-3 7.076-5.288 7.738zM16.967 19.92l0-6.939c2.291-0.021 4.218-0.118 5.818-0.25 0.131 1.053 0.203 2.147 0.203 3.268 0 1.442-0.116 2.84-0.329 4.16-1.575-0.128-3.462-0.219-5.692-0.24zM28.588 9.81c-0.302 0.094-1.564 0.453-4.094 0.751-0.564-2.998-1.584-5.561-2.91-7.412 3.048 1.325 5.535 3.697 7.005 6.661zM11.213 2.831c-1.632 1.873-2.963 4.568-3.691 7.754-2.265-0.245-3.623-0.534-4.166-0.665 1.585-3.27 4.407-5.836 7.856-7.088zM2.614 11.787c0.385 0.104 1.841 0.467 4.549 0.766-0.155 1.107-0.24 2.26-0.24 3.447 0 1.509 0.136 2.96 0.383 4.334-2.325 0.251-3.755 0.552-4.396 0.706-0.607-1.566-0.944-3.264-0.944-5.041 0-1.467 0.228-2.883 0.649-4.213zM3.784 22.886c0.727-0.154 2.029-0.39 3.956-0.591 0.759 2.803 1.993 5.175 3.473 6.874-3.16-1.148-5.79-3.398-7.429-6.282v0zM21.583 28.849c1.195-1.665 2.14-3.907 2.728-6.525 1.982 0.227 3.226 0.494 3.853 0.652-1.5 2.596-3.808 4.669-6.581 5.873z"></path>
                  </svg>
                </div>
                <h3>Web Pages</h3>
              </div>
              <div className="card__body">
                <p>
                  Detailed school website setup and content management guide to customize
                  and maintain your institution's web presence effectively.
                </p>
              </div>
              <div className="card__footer">
                <Link
                  className="button button--outline button--primary"
                  to="/web-page/"
                >
                  Web Pages Setup
                </Link>
              </div>
            </div>
          </div>
          <div className="col col--3">
            <div className="card margin-bottom--lg">
              <div className="card__header">
                <div className={styles.cardIcon}>
                  {/* Features Icon */}
                  <svg fill="currentColor" width="32px" height="32px" viewBox="0 0 56 56" xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M 23.6641 52.3985 C 26.6407 55.375 29.3594 55.3516 32.3126 52.3985 L 35.9219 48.8125 C 36.2969 48.4610 36.6250 48.3203 37.1172 48.3203 L 42.1797 48.3203 C 46.3749 48.3203 48.3204 46.3985 48.3204 42.1797 L 48.3204 37.1172 C 48.3204 36.625 48.4610 36.2969 48.8124 35.9219 L 52.3749 32.3125 C 55.3749 29.3594 55.3514 26.6407 52.3749 23.6641 L 48.8124 20.0547 C 48.4610 19.7031 48.3204 19.3516 48.3204 18.8829 L 48.3204 13.7969 C 48.3204 9.625 46.3985 7.6563 42.1797 7.6563 L 37.1172 7.6563 C 36.6250 7.6563 36.2969 7.5391 35.9219 7.1875 L 32.3126 3.6016 C 29.3594 .6250 26.6407 .6485 23.6641 3.6016 L 20.0547 7.1875 C 19.7032 7.5391 19.3516 7.6563 18.8828 7.6563 L 13.7969 7.6563 C 9.6016 7.6563 7.6563 9.5782 7.6563 13.7969 L 7.6563 18.8829 C 7.6563 19.3516 7.5391 19.7031 7.1876 20.0547 L 3.6016 23.6641 C .6251 26.6407 .6485 29.3594 3.6016 32.3125 L 7.1876 35.9219 C 7.5391 36.2969 7.6563 36.625 7.6563 37.1172 L 7.6563 42.1797 C 7.6563 46.3750 9.6016 48.3203 13.7969 48.3203 L 18.8828 48.3203 C 19.3516 48.3203 19.7032 48.4610 20.0547 48.8125 Z M 26.2891 49.7734 L 21.8828 45.3438 C 21.3672 44.8047 20.8282 44.5938 20.1016 44.5938 L 13.7969 44.5938 C 11.7110 44.5938 11.3828 44.2656 11.3828 42.1797 L 11.3828 35.875 C 11.3828 35.1719 11.1719 34.6329 10.6563 34.1172 L 6.2266 29.7109 C 4.7501 28.2109 4.7501 27.7891 6.2266 26.2891 L 10.6563 21.8829 C 11.1719 21.3672 11.3828 20.8282 11.3828 20.1016 L 11.3828 13.7969 C 11.3828 11.6875 11.6876 11.3829 13.7969 11.3829 L 20.1016 11.3829 C 20.8282 11.3829 21.3672 11.1953 21.8828 10.6563 L 26.2891 6.2266 C 27.7891 4.7500 28.2110 4.7500 29.7110 6.2266 L 34.1172 10.6563 C 34.6328 11.1953 35.1719 11.3829 35.8750 11.3829 L 42.1797 11.3829 C 44.2657 11.3829 44.5938 11.7109 44.5938 13.7969 L 44.5938 20.1016 C 44.5938 20.8282 44.8282 21.3672 45.3439 21.8829 L 49.7733 26.2891 C 51.2498 27.7891 51.2498 28.2109 49.7733 29.7109 L 45.3439 34.1172 C 44.8282 34.6329 44.5938 35.1719 44.5938 35.875 L 44.5938 42.1797 C 44.5938 44.2656 44.2657 44.5938 42.1797 44.5938 L 35.8750 44.5938 C 35.1719 44.5938 34.6328 44.8047 34.1172 45.3438 L 29.7110 49.7734 C 28.2110 51.2500 27.7891 51.2500 26.2891 49.7734 Z M 24.3438 39.2266 C 25.0235 39.2266 25.5391 38.9453 25.8907 38.5234 L 38.8985 20.3360 C 39.1563 19.9609 39.2969 19.5391 39.2969 19.1407 C 39.2969 18.1094 38.5001 17.2891 37.4219 17.2891 C 36.6485 17.2891 36.2266 17.5469 35.7579 18.2266 L 24.2735 34.3985 L 18.3438 27.8594 C 17.9454 27.4141 17.5001 27.2266 16.9141 27.2266 C 15.7657 27.2266 14.9454 28.0000 14.9454 29.0782 C 14.9454 29.5469 15.1094 29.9922 15.4376 30.3203 L 22.8907 38.6172 C 23.2423 38.9922 23.6876 39.2266 24.3438 39.2266 Z" />
                  </svg>
                </div>
                <h3>Features</h3>
              </div>
              <div className="card__body">
                <p>
                  Comprehensive feature guide with video tutorials on using all aspects of the eSchool project,
                  showcasing the complete functionality available to all user roles.
                </p>
              </div>
              <div className="card__footer">
                <Link
                  className="button button--outline button--primary"
                  to="/features/"
                >
                  Explore Features
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

function SupportSection() {
  return (
    <section className={styles.support}>
      <div className="container text--center">
        <h2>Need Help?</h2>
        <p>
          Our support team is ready to assist you with any questions or issues.
        </p>
        <div className={styles.buttons}>
          <Link
            className="button button--primary button--lg margin-right--md"
            to="/support/"
          >
            Support
          </Link>
          <Link className="button button--secondary button--lg" to="/faqs/">
            FAQs
          </Link>
        </div>
      </div>
    </section>
  );
}

export default function Home() {
  const { siteConfig } = useDocusaurusContext();
  return (
    <Layout title={siteConfig.title} description={siteConfig.tagline}>
      <HomepageHeader />
      <main>
        <HomepageFeatures />
        <DocBanner />
        <SupportSection />
      </main>
    </Layout>
  );
}
