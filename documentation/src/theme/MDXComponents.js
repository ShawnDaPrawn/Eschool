import React from 'react';
import MDXComponents from '@theme-original/MDXComponents';

// Custom component for YouTube videos
function YouTubeVideo({ id, title }) {
    return (
        <div className="container-iframe">
            <iframe
                className="responsive-iframe"
                src={`https://www.youtube.com/embed/${id}`}
                title={title || 'YouTube video player'}
                style={{ border: 0 }}
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowFullScreen
            />
        </div>
    );
}

// Custom component for local videos
function Video({ src, type = "video/webm", title = "Video player" }) {
    return (
        <div className="video-container">
            <video width="100%" controls title={title}>
                <source src={src} type={type} />
                Your browser does not support the video tag.
            </video>
        </div>
    );
}

export default {
    ...MDXComponents,
    YouTube: YouTubeVideo,
    Video: Video,
}; 