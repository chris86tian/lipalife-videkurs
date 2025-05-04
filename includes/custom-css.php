<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Custom CSS einbinden
function lipalife_videokurs_custom_css() {
    echo '<style>
    .svl-complete-button {
        position: absolute;
        top: -60px;
        right: -20px;
        z-index: 999;
    }
    .svl-wrapper {
        border: 1px solid #ccc;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 20px;
        background-color: #fff;
        margin-bottom: 80px;
        position: relative;
    }
    .svl-lesson-list {
        list-style: none;
        padding-left: 0;
    }
    .svl-lesson-list li {
        margin-bottom: 8px;
        font-size: 16px;
        line-height: 1.6;
    }
    .svl-lesson-list li a {
        display: block;
        padding: 4px 0;
    }
    .svl-lesson-list li a.completed {
        color: #28a745;
        font-weight: normal;
    }
    .svl-lesson-list li a.active {
        font-weight: bold;
        color: #007bff;
        text-decoration: underline;
    }
    .svl-term-item {
        margin-bottom: 15px;
    }
    .svl-term-item h5 {
        margin-top: 0;
        margin-bottom: 8px;
        font-size: 17px;
        color: #333;
    }
    .svl-term-item .svl-lesson-list {
        padding-left: 15px;
    }
    .svl-courses-overview {
        margin-bottom: 80px;
    }
    @media (max-width: 768px) {
        .svl-wrapper {
            padding: 15px;
        }
        .svl-complete-button {
            position: static;
            margin-top: 10px;
            margin-bottom: 10px;
            right: auto;
            top: auto;
            text-align: right;
        }
        .svl-wrapper > div {
            flex-direction: column;
            gap: 15px;
        }
        .svl-wrapper > div > div {
            flex: 1 1 100% !important;
            min-width: auto !important;
        }
        .svl-courses-overview {
            flex-direction: column;
            gap: 15px;
        }
        .svl-courses-overview > div {
            flex: 1 0 100% !important;
        }
    }
    </style>';
}
add_action('wp_head', 'lipalife_videokurs_custom_css');
