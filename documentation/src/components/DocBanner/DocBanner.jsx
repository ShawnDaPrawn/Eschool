import React from 'react';
import Link from '@docusaurus/Link';
import styles from './DocBanner.module.css';

export default function DocBanner() {
  return (
    <aside className={styles.banner} aria-label="Recommended setup order">
      <div className={styles.heading}>
        <span className={styles.label}>First-time setup</span>
        <h2>A good start, in three steps.</h2>
        <p>Get the foundation ready, then connect the rest.</p>
      </div>
      <ol className={styles.steps}>
        <li><Link to="/admin-panel/"><span className={styles.number}>01</span><span>Set up your school<span className={styles.detail}>Start with the admin panel</span></span></Link></li>
        <li><Link to="/mobile-app/"><span className={styles.number}>02</span><span>Connect your apps<span className={styles.detail}>Bring learners and teachers in</span></span></Link></li>
        <li><Link to="/features/"><span className={styles.number}>03</span><span>Find your flow<span className={styles.detail}>Explore everyday features</span></span></Link></li>
      </ol>
    </aside>
  );
}
