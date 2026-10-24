import React from 'react';
import Link from '@docusaurus/Link';
import useBaseUrl from '@docusaurus/useBaseUrl';
import Layout from '@theme/Layout';
import Heading from '@theme/Heading';
import DocBanner from '../components/DocBanner/DocBanner';
import styles from './index.module.css';

const guides = [
  { icon: 'admin', audience: 'School administrators', title: 'Admin panel',
    description: 'Set up your school, configure settings, and bring your people and information together.',
    to: '/admin-panel/', action: 'Set up the admin panel' },
  { icon: 'phone', audience: 'App developers', title: 'Mobile apps',
    description: 'Configure NavigateEd for learners and parents, and NavigateEd Teacher for your teaching team.',
    to: '/mobile-app/', action: 'Connect the apps' },
  { icon: 'website', audience: 'Website administrators', title: 'School website',
    description: 'Make the public website your own, from school information to galleries and contact details.',
    to: '/web-page/', action: 'Manage your website' },
  { icon: 'book', audience: 'School teams', title: 'Everyday features',
    description: 'Find practical guides to attendance, assignments, exams, communication, and more.',
    to: '/features/', action: 'Explore the features' },
];

function Icon({ name }) {
  const paths = {
    admin: 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 9h.01M15 9h.01M9 12h.01M15 12h.01',
    phone: 'M9 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2ZM11 6h2M12 18h.01',
    website: 'M3 9h18M7 6h.01M10 6h.01M13 6h.01M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2ZM8 13h8M8 17h5',
    book: 'M12 5v16M3 3h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5v16h-5a4 4 0 0 0-4 2 4 4 0 0 0-4-2H3V3Z',
    arrow: 'M4 12h16M14 6l6 6-6 6',
  };
  return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6"
    strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" focusable="false"><path d={paths[name]} /></svg>;
}

function HomepageHeader() {
  const logo = useBaseUrl('/navigateed/reversed.svg');
  return (
    <header className={styles.hero}>
      <div className={`container ${styles.heroLayout}`}>
        <div>
          <p className={styles.eyebrow}>NavigateEd documentation</p>
          <h1>A little guidance.<br />A world of possibility.</h1>
          <p className={styles.intro}>Your guide to setting up NavigateEd and making everyday school life simpler.
            Find your starting point, then take it one step at a time.</p>
          <div className={styles.actions}>
            <Link className="m3-button m3-button--inverse m3-button--large" to="#guides">Find your guide <Icon name="arrow" /></Link>
            <Link className="m3-button m3-button--outlined m3-button--inverse m3-button--large" to="/faqs/">Common questions</Link>
          </div>
        </div>
        <aside className={styles.startCard} aria-labelledby="start-title">
          <img className={styles.heroLogo} src={logo} alt="NavigateEd" width="230" height="67" />
          <div className={styles.startDivider} />
          <p className={styles.eyebrow}>New here?</p>
          <h2 id="start-title">Start with your school.</h2>
          <p>The admin panel connects your school website and both mobile apps. Set it up first, then build from there.</p>
          <Link className={styles.startLink} to="/admin-panel/">Open the setup guide <Icon name="arrow" /></Link>
        </aside>
      </div>
    </header>
  );
}

function HomepageGuides() {
  return (
    <section className={styles.guides} aria-labelledby="guides">
      <div className="container">
        <div className={styles.sectionHeading}>
          <div><p className={styles.eyebrow}>A place for every question</p><Heading as="h2" id="guides">What would you like to do?</Heading></div>
          <p>From first-time setup to the tasks you do every day.</p>
        </div>
        <div className={styles.guideGrid}>
          {guides.map((guide) => (
            <Link key={guide.to} to={guide.to} className={`m3-card m3-card--outlined ${styles.guideCard}`}>
              <span className={styles.guideIcon}><Icon name={guide.icon} /></span>
              <p className={styles.audience}>{guide.audience}</p>
              <h3>{guide.title}</h3><p className={styles.description}>{guide.description}</p>
              <span className={styles.guideAction}>{guide.action}<Icon name="arrow" /></span>
            </Link>
          ))}
        </div>
        <DocBanner />
      </div>
    </section>
  );
}

function SupportSection() {
  return (
    <section className={styles.support} aria-labelledby="support-title">
      <div className={`container ${styles.supportLayout}`}>
        <div>
          <p className={styles.eyebrow}>Keep moving forward</p>
          <h2 id="support-title">A helping hand when you need it.</h2>
          <p>Check common questions, find support, or catch up on what has changed.</p>
        </div>
        <div className={styles.actions}>
          <Link className="m3-button m3-button--filled m3-button--large" to="/support/">Find support</Link>
          <Link className="m3-button m3-button--outlined m3-button--large" to="/changelog/">What’s new</Link>
        </div>
      </div>
    </section>
  );
}

export default function Home() {
  return (
    <Layout title="Documentation" description="Setup guides and everyday help for NavigateEd, NavigateEd Teacher, your admin panel, and your school website.">
      <main id="main-content" className={styles.home}>
        <HomepageHeader /><HomepageGuides /><SupportSection />
      </main>
    </Layout>
  );
}
