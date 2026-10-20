---
sidebar_position: 1
title: Changelog
---

# Changelog

## Version 3.5.0 (20 Jul 2026)

- **[Added]** Student Reports for Attendance, Fees, Exams, Assignments, and Leaves
- **[Added]** Teacher ID Card view and download support
- **[Added]** Teacher & Staff Attendance and Attendance Reports
- **[Added]** Support for four attendance types with leave-aware validation
- **[Added]** Staff ID Card customization in the Admin Panel
- **[Added]** School Admin support for Teacher & Staff Leave Management
- **[Added]** Custom email templates for Student Admission and Teacher/Staff creation
- **[Added]** Month-wise Student Attendance Reports
- **[Improved]** Assignment submission modal UI in the Admin Panel
- **[Improved]** Class details view with semester information
- **[Added]** Backup reminder before system updates in the Admin Panel
- **[Fixed]** Google reCAPTCHA integration issues
- **[Updated]** Android application code migrated from Java to Kotlin
- **[Updated]** Application codes are now compatible with Flutter 3.44.6

## Version 3.4.3 (22 Jun 2026)

- **[Improved]** Major Admin Panel UI/UX redesign and dashboard enhancements
- **[Added]** Student Profile UI improvements with ID Card view and download support in app
- **[Added]** Enhanced ID Card customization options with real-time preview support in admin panel
- **[Improved]** Role management, online exam management, timetable workflows, teacher management, validations, and overall system stability

## Version 3.4.2 (25 May 2026)

- **[Fixed]** Minor issues to improve stability, performance, and user experience

## Version 3.4.1 (15 May 2026)

- **[Added]** Session year–wise semester management for classes
- **[Added]** Semester-aware support across academic modules and applications
- **[Enhanced]** Offline exam results with semester-wise support and dedicated result management
- **[Updated]** Application codes are now compatible with Flutter 3.41.9

## Version 3.4.0 (12 March 2026)

- **[Added]** Session year–wise data structure across the system
- **[Added]** Global session year dropdown filter in panel header
- **[Added]** Gallery module in both Teacher and Student apps
- **[Added]** Option to delete unnecessary data of a session year
- **[Added]** Force delete for interlinked data (related records will be deleted together)
- **[Improved]** CRUD operations are now session-aware

## Version 3.3.6 (22 December 2025)

- **[Improved]** Promotion flow in Admin Panel
- **[Added]** School Website “Explore” option in both apps without login
- **[Added]** Firebase Crash Analytics in both apps
- **[Fixed]** UI issues from Android 16 edge-to-edge display changes

## Version 3.3.5 (21 November 2025)

- **[Added]** Admin control for student leave requests
- **[Added]** Academic Calendar PDF download
- **[Added]** Admin ability to assign or modify students’ elective subjects
- **[Added]** Semester start/end dates and break details in Academic Calendar
- **[Added]** Current session year shown in Admin Panel header
- **[Improved]** Teachers with permission can add students to any class
- **[Fixed]** Leave rejection reason not displaying

## Version 3.3.4 (17 October 2025)

- **[Updated]** Backend code to work with Laravel 12 with PHP 8.3
- **[Added]** Firebase topic-based notifications
- **[Added]** Android 16KB page size support
- **[Updated]** Both Flutter application codes now compatible with version 3.35
- **[Improved]** Bug fixes and performance enhancements

## Version 3.3.3 (18 April 2025)

- **[Updated]** App compatibility with Flutter 3.29 & Android 15 (SDK 35)
- **[Fixed]** Leave request status change issue in teacher app
- **[Fixed]** Razorpay release mode crash (ProGuard rules added)

## Version 3.3.2 (6 February 2025)

- **[Re-designed]** Teacher app home for a better layout and organised sections  
- **[Added]** Staff Leaves Section in Teacher panel dashboard  
- **[Added]** Option for teachers to manage class timetable links  
- **[Updated]** Both apps now compatible with Flutter 3.27 & AGP 8

## Version 3.3.1 (13 November 2024)

- **[Added]** Student Leave Management
- **[Added]** FlutterWave Payment Gateway
- **[Enhanced]** PayStack Payment Gateway with web-view for expanded payment options
- **[Updated]** Multi-gateway support, letting users choose their preferred payment method
- **[Added]** Captcha in admission and contact forms to prevent spam

## Version 3.3.0 (17 September 2024)

- **[Added]** Student self-registration from web with admin approval
- **[Added]** Semester management for classes
- **[Enhanced]** Combined academic calendar for holidays, events, and exams
- **[Improved]** Bug fixes and improvements
- **[Improved]** Application codes are now compatible with Flutter 3.24.2

## Version 3.2.1 (19 July 2024)

- **[Added]** Leave Management in the Teacher application
- **[Added]** Chat Settings: 
  - Clear old chats automatically or manually 
  - Manage message-sending restrictions effortlessly from the admin panel
- **[Added]** Leaves list with filters on admin and teacher panel dashboards
- **[Added]** Cache clearing option in admin panel
- **[Added]** Attendance Report viewing in panel
- **[Enhanced]** Assignment submission allows students to include textual information
- **[Modified]** Education programs to be created in the academics section and linked to classes

## Version 3.2.0 (26 June 2024)

- **[Added]** Staff & Teacher Leave Management
- **[Enhanced]** Fee Payment System: 
  - Option to enable Students to pay their fees
  - Option to enforce compulsory payments after the due date
- **[Added]** Online Class/Custom Link Integration with Timetable
- **[Modified]** School web footer now shows the same copyright text as the panel
- **[Improved]** Updated application code to be compatible with Flutter 3.22.2 and migrated away from deprecated code

## Version 3.1.1 (24 May 2024)

- **[Added]** Academic documents generation: Easily create Bonafide Certificates, Leaving Certificates, Offline Exam Results, and ID Cards
- **[Added]** Customizable ID cards: Tailor ID cards with adjustable fields and styles
- **[Added]** Teacher & Parent form custom fields
- **[Improved]** Assignment submission: Undo submissions to re-submit new files

## Version 3.1.0 (12 April 2024)

- **[Added]** Staff Management functionality
- **[Added]** School Events feature in apps and website
- **[Added]** Option to hide sections on the website
- **[Added]** IOS privacy API use reasons (PrivacyInfo.xcprivacy)
- **[Improved]** UI/UX in the Student app's dashboard
- **[Improved]** Online Exam Interface with Intuitive Navigation Options
- **[Updated]** App Codes to be Compatible with the latest Flutter version (3.19.5)
- **[Updated]** Firebase notifications to utilize HTTP v1 APIs, migrating from Legacy API

## Version 3.0.0 (28 February 2024)

- **[Added]** eSchool Web: A platform for showcasing your school
- **[Added]** Dynamic web settings within the admin panel, enabling seamless modification of website data
- **[Enhanced]** Flutter application codes to ensure compatibility with the latest version, optimising performance and user experience

## Version 2.1.1 (22 January 2024)

- **[Added]** User Search in chat for all users, across both apps
- **[Added]** PayStack payment gateway for seamless fee transactions
- **[Improved]** Grouped chat notifications on Android for a cleaner message experience
- **[Improved]** Admin and teacher panel navigation bar UI
- **[Improved]** User Interface for force update and maintenance views in both applications
- **[Fixed]** Resolved panel form bugs in student admission and lesson editing
- **[Fixed]** Addressed Stripe payment gateway package issues
- **[Fixed]** Assigned assignments not showing for some students
- **[Modified]** Demo Mode functionality is now retrieving the on/off value from the backend, eliminating the need for application-side adjustments by customers

## Version 2.1.0 (3 January 2024)

- **[Added]** Chat Module:
  - Facilitates communication between teachers and students and between teachers and parents in both applications
  - Real-time messaging via Notifications for prompt and free communication
  - Supports sharing text, links, images, PDFs and files with customisable restrictions
  - Unread chats and unread message count is maintained for users

## Version 2.0.2 (6 November 2023)

- **[Added]** Dynamic fields for the Student Admission Form
- **[Added]** Custom Notifications feature
- **[Added]** Push Notifications within the Teacher app
- **[Added]** Image and PDF viewing capabilities directly within the applications
- **[Modified]** Students can now edit the submitted assignment files once during the review process
- **[Modified]** Optional parental fields; for students without parents' guardian details can be entered exclusively and guardians can log in as their parents from the application
- **[Enhanced]** Improved online payment security, addressed minor bugs, and refined the user interface for a better experience

## Version 2.0.1 (21 September 2023)

- **[Added]** Bulk attendance adding feature from Teacher Panel
- **[Added]** Statistics in Admin panel dashboard
- **[Added]** Item appearance animations in both applications with customisation options in code
- **[Fixed]** Resolved the problem causing failures in Stripe payment gateway transactions when attempting to pay excessive fees at once
- **[Added]** Notification to parents when their child is absent
- **[Added]** Changeable background image in the panel's login page
- **[Modified]** Documentation now includes informative Feature usage videos
- **[Improved]** Addressed bugs, enhanced the design of the user interface on several screens, and implemented code enhancements

## Version 2.0.0 (21 August 2023)

- **[Added]** eSchool Teacher application with eSchool project
- **[Modified]** One teacher can be a class teacher of multiple classes
- **[Added]** Streams in which classes can be sub-divided In
- **[Added]** Shifts feature in which classes can be taken shift-wise
- **[Fixed]** Bugs in parent login and in the admin panel

## Version 1.0.7 (12 July 2023)

- **[Improved]** Fees Module with instalment payment feature
- **[Added]** Paid fees receipt pdf downloading feature
- **[Added]** Fees transaction screen
- **[Modified]** Due charges and due date added when creating a new session year
- **[Improved]** Bugs fixed and code improved

## Version 1.0.6 (20 June 2023)

- **[Fixed]** File download/upload not working on Android 13+ devices
- **[Updated]** Compatible with Flutter version 3.10.5
- **[Improved]** Various improvements and bug fixes

## Version 1.0.5 (3 February 2023)

- **[Added]** Online Exam Module
- **[Added]** Teacher can have permission to manage the student's data
- **[Added]** Generate Roll Number for Students
- **[Improved]** Admin & Teacher Panel improvements

## Version 1.0.4 (8 December 2022)

- **[Added]** Fees Module
- **[Added]** Unique Subject validation
- **[Added]** New Design for Timetable Module
- **[Improved]** Admin & Teacher Panel improvements

## Version 1.0.3 (15 September 2022)

- **[Added]** Exam & Exam Timetable Module
- **[Added]** Grade & Result Module
- **[Added]** Super Admin Change Profile
- **[Added]** Default Session Year
- **[Added]** Student Bulk Admission using CSV
- **[Improved]** Teacher & Admin Panel improvements

## Version 1.0.2 (25 July 2022)

- **[Added]** RTL support with admin panel
- **[Improved]** Youtube URL validation on lessons and topics from admin panel
- **[Added]** Table filters in admin panel
- **[Improved]** Force update feature with app

## Version 1.0.1 (18 June 2022)

- **[Added]** System Validator
