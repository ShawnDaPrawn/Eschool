import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/app/deepLinks.dart';
import 'package:eschool_teacher/app/sharedScreens.dart';
import 'package:eschool_teacher/ui/screens/aboutUsScreen.dart';
import 'package:eschool_teacher/ui/screens/academicCalendar/academicCalendarScreen.dart';
import 'package:eschool_teacher/ui/screens/addOrEditAnnouncementScreen.dart';
import 'package:eschool_teacher/ui/screens/addOrEditAssignmentScreen.dart';
import 'package:eschool_teacher/ui/screens/addOrEditLessonScreen.dart';
import 'package:eschool_teacher/ui/screens/addOrEditTopicScreen.dart';
import 'package:eschool_teacher/ui/screens/announcementsScreen.dart';
import 'package:eschool_teacher/ui/screens/assignment/assignmentScreen.dart';
import 'package:eschool_teacher/ui/screens/assignments/assignmentsScreen.dart';
import 'package:eschool_teacher/ui/screens/attendanceScreen.dart';
import 'package:eschool_teacher/ui/screens/chat/chatMessagesScreen.dart';
import 'package:eschool_teacher/ui/screens/chat/chatUserProfileScreen.dart';
import 'package:eschool_teacher/ui/screens/chat/chatUserSearchScreen.dart';
import 'package:eschool_teacher/ui/screens/chat/chatUsersScreen.dart';
import 'package:eschool_teacher/ui/screens/class/classScreen.dart';
import 'package:eschool_teacher/ui/screens/contactUsScreen.dart';
import 'package:eschool_teacher/ui/screens/eventsDetailsScreen.dart';
import 'package:eschool_teacher/ui/screens/exam/examScreen.dart';
import 'package:eschool_teacher/ui/screens/exam/examTimeTableScreen.dart';
import 'package:eschool_teacher/ui/screens/fileViews/imageFileScreen.dart';
import 'package:eschool_teacher/ui/screens/fileViews/pdfFileScreen.dart';
import 'package:eschool_teacher/ui/screens/gallery/galleryAlbumPhotosScreen.dart';
import 'package:eschool_teacher/ui/screens/gallery/galleryScreen.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:eschool_teacher/ui/screens/leave/addLeaveScreen.dart';
import 'package:eschool_teacher/ui/screens/leave/manageLeavesScreen.dart';
import 'package:eschool_teacher/ui/screens/lessonsScreen.dart';
import 'package:eschool_teacher/ui/screens/login/loginScreen.dart';
import 'package:eschool_teacher/ui/screens/login/policyScreen.dart';
import 'package:eschool_teacher/ui/screens/notificationsScreen.dart';
import 'package:eschool_teacher/ui/screens/privacyPolicyScreen.dart';
import 'package:eschool_teacher/ui/screens/result/addResultForAllStudentsScreen.dart';
import 'package:eschool_teacher/ui/screens/result/addResultOfStudentScreen.dart';
import 'package:eschool_teacher/ui/screens/result/resultScreen.dart';
import 'package:eschool_teacher/ui/screens/searchStudentScreen.dart';
import 'package:eschool_teacher/ui/screens/splashScreen.dart';
import 'package:eschool_teacher/ui/screens/studentDetails/studentDetailsScreen.dart';
import 'package:eschool_teacher/ui/screens/studentLeaves/manageStudentLeavesScreen.dart';
import 'package:eschool_teacher/ui/screens/subjectScreen.dart';
import 'package:eschool_teacher/ui/screens/teacherAttendance/teacherAttendanceScreen.dart';
import 'package:eschool_teacher/ui/screens/termsAndConditionScreen.dart';
import 'package:eschool_teacher/ui/screens/topicsByLessonScreen.dart';
import 'package:eschool_teacher/ui/screens/topicsScreen.dart';
import 'package:flutter/material.dart';

// ignore: avoid_classes_with_only_static_members
class Routes {
  static const String splash = 'splash';
  static const String policies = 'policies';
  static const String home = '/';
  static const String login = 'login';
  static const String classScreen = '/class';
  static const String subject = '/subject';

  static const String assignments = '/assignments';

  static const String announcements = '/announcements';

  static const String topics = '/topics';

  static const String assignment = '/assignment';

  static const String addAssignment = '/addAssignment';

  static const String attendance = '/attendance';

  static const String searchStudent = '/searchStudent';

  static const String studentDetails = '/studentDetails';

  static const String resultList = '/resultList';

  static const String addResult = '/addResult';
  static const String addResultForAllStudents = '/addResultForAllStudents';

  static const String lessons = '/lessons';

  static const String addOrEditLesson = '/addOrEditLesson';

  static const String addOrEditTopic = '/addOrEditTopic';

  static const String addOrEditAnnouncement = '/addOrEditAnnouncement';

  static const String monthWiseAttendance = '/monthWiseAttendance';

  static const String termsAndCondition = '/termsAndCondition';

  static const String aboutUs = '/aboutUs';
  static const String privacyPolicy = '/privacyPolicy';

  static const String contactUs = '/contactUs';

  static const String topicsByLesson = '/topicsByLesson';

  static const String academicCalendar = '/academicCalendar';

  static const String exams = '/exam';
  static const String examTimeTable = '/examTimeTable';

  static const String notifications = '/notifications';

  static const String pdfFileView = '/pdfFileView';
  static const String imageFileView = '/imageFileView';

  static const String chatMessages = '/chatMessages';
  static const String chatUserPage = '/chatUserPage';
  static const String chatUserProfile = '/chatUserProfile';
  static const String chatUserSearch = '/chatUserSearch';

  static const String eventDetails = '/eventDetails';

  static const String manageLeaves = '/manageLeaves';
  static const String addLeave = '/addLeave';

  static const String manageStudentLeaves = '/manageStudentLeaves';

  //the screens built once in the shared packages (sharedScreens.dart)
  static const String notificationSettings = '/notifications/settings';
  static const String mySchool = '/my-school';
  static const String academicMarks = '/academics/marks';
  static const String academicReports = '/academics/reports';
  static const String academicContent = '/academics/content';
  static const String mastery = '/growth/mastery';
  static const String dailyPractice = '/learning/practice';
  static const String progressNotes = '/growth/progress';
  static const String myGrade = '/growth/my-grade';
  static const String teachingAssistant = '/learning/teaching-assistant';
  static const String testInsights = '/learning/test-insights';
  static const String schoolDiscipline = '/discipline';
  static const String privateGallery = '/community/private-gallery';
  static const String recognition = '/community/recognition';
  static const String riskInterventions = '/growth/risk';
  static const String reliefCover = '/pastoral/cover';
  static const String supportPlans = '/pastoral/support';
  static const String wellbeing = '/wellbeing';
  static const String schoolCards = '/school-cards';
  static const String consultations = '/consultations';
  static const String activityGroups = '/activities/groups';
  static const String schoolMessages = '/communication/messages';
  static const String academicTimetable = '/academics/timetable';
  static const String academicRegisters = '/academics/registers';
  static const String profilePicture = '/profile-picture';
  static const String profileDetails = '/profile-details';
  static const String handles = '/handles';

  static const String gallery = SharedRoutes.gallery;
  static const String galleryAlbumPhotos = SharedRoutes.galleryAlbumPhotos;

  static const String teacherAttendence = '/teacherAttendence';

  static String currentRoute = splash;

  static Route<dynamic> onGenerateRouted(RouteSettings routeSettings) {
    final route = _buildRoute(routeSettings);
    final module = switch (routeSettings.name) {
      progressNotes => 'academics',
      myGrade => 'my_grade',
      teachingAssistant => 'teaching_assistant',
      testInsights => 'test_insights',
      schoolDiscipline => 'discipline',
      riskInterventions => 'early_warning',
      mastery => 'mastery',
      consultations => 'consultations',
      schoolCards => 'cards',
      dailyPractice => 'daily_practice',
      activityGroups => 'groups',
      schoolMessages => 'communication',
      reliefCover => 'relief_cover',
      supportPlans => 'support_plans',
      wellbeing => 'wellbeing',
      chatMessages ||
      chatUserProfile ||
      chatUserPage ||
      chatUserSearch => 'chat',
      _ => null,
    };
    return module == null ? route : guardModuleRoute(route, module);
  }

  static Route<dynamic> _buildRoute(RouteSettings routeSettings) {
    currentRoute = routeSettings.name ?? '';

    //a link that opened the app, or came before sign-in, opens over home
    if (currentRoute == home) {
      openPendingDeepLink();
    }

    debugPrint('Route: $currentRoute');

    switch (routeSettings.name) {
      case splash:
        {
          return SplashScreen.route(routeSettings);
        }
      case login:
        {
          return LoginScreen.route(routeSettings);
        }
      case policies:
        {
          return PolicyScreen.route(routeSettings);
        }
      case home:
        {
          return HomeScreen.route(routeSettings);
        }
      case classScreen:
        {
          return ClassScreen.route(routeSettings);
        }
      case subject:
        {
          return SubjectScreen.route(routeSettings);
        }
      case assignments:
        {
          return AssignmentsScreen.route(routeSettings);
        }
      case assignment:
        {
          return AssignmentScreen.route(routeSettings);
        }
      case addAssignment:
        {
          return AddAssignmentScreen.routes(routeSettings);
        }

      case attendance:
        {
          return AttendanceScreen.route(routeSettings);
        }
      case searchStudent:
        {
          return SearchStudentScreen.route(routeSettings);
        }
      case studentDetails:
        {
          return StudentDetailsScreen.route(routeSettings);
        }
      case resultList:
        {
          return ResultListScreen.route(routeSettings);
        }
      case addResult:
        {
          return AddResultScreen.route(routeSettings);
        }
      case addResultForAllStudents:
        {
          return AddResultForAllStudents.route(routeSettings);
        }

      case announcements:
        {
          return AnnouncementsScreen.route(routeSettings);
        }
      case lessons:
        {
          return LessonsScreen.route(routeSettings);
        }
      case topics:
        {
          return TopicsScreen.route(routeSettings);
        }
      case addOrEditLesson:
        {
          return AddOrEditLessonScreen.route(routeSettings);
        }
      case addOrEditTopic:
        {
          return AddOrEditTopicScreen.route(routeSettings);
        }
      case aboutUs:
        {
          return AboutUsScreen.route(routeSettings);
        }
      case privacyPolicy:
        {
          return PrivacyPolicyScreen.route(routeSettings);
        }

      case contactUs:
        {
          return ContactUsScreen.route(routeSettings);
        }
      case termsAndCondition:
        {
          return TermsAndConditionScreen.route(routeSettings);
        }
      case addOrEditAnnouncement:
        {
          return AddOrEditAnnouncementScreen.route(routeSettings);
        }
      case topicsByLesson:
        {
          return TopcisByLessonScreen.route(routeSettings);
        }
      case academicCalendar:
        {
          return AcademicCalendarScreen.route(routeSettings);
        }
      case exams:
        {
          return ExamScreen.route(routeSettings);
        }

      case examTimeTable:
        {
          return ExamTimeTableScreen.route(routeSettings);
        }
      case notifications:
        return NotificationScreen.route(routeSettings);
      case pdfFileView:
        return PdfFileScreen.route(routeSettings);
      case imageFileView:
        return ImageFileScreen.route(routeSettings);
      case chatMessages:
        return ChatMessagesScreen.route(routeSettings);
      case chatUserProfile:
        return ChatUserProfileScreen.route(routeSettings);
      case chatUserPage:
        return ChatUsersScreen.route(routeSettings);
      case chatUserSearch:
        return ChatUsersSearchScreen.route(routeSettings);
      case eventDetails:
        return EventDetailsScreen.route(routeSettings);
      case manageLeaves:
        return ManageLeavesScreen.route(routeSettings);
      case addLeave:
        return AddLeaveScreen.route(routeSettings);
      case manageStudentLeaves:
        return ManageStudentLeavesScreen.route(routeSettings);
      case gallery:
        return GalleryScreen.route(routeSettings);
      case galleryAlbumPhotos:
        return GalleryAlbumPhotosScreen.route(routeSettings);

      case notificationSettings:
        return notificationSettingsRoute();

      case academicRegisters:
        return academicRegistersRoute();
      case academicTimetable:
        return academicTimetableRoute();
      case progressNotes:
        return progressNotesRoute();
      case myGrade:
        return myGradeRoute();
      case teachingAssistant:
        return teachingWorkspaceRoute();
      case testInsights:
        return teachingWorkspaceRoute(insights: true);
      case schoolDiscipline:
        return schoolDisciplineRoute();
      case privateGallery:
        return staffPrivateGalleryRoute();
      case recognition:
        return staffRecognitionRoute();
      case riskInterventions:
        return riskInterventionsRoute();
      case dailyPractice:
        return dailyPracticeRoute();
      case mastery:
        return masteryRoute();
      case reliefCover:
        return reliefCoverRoute();
      case supportPlans:
        return supportPlansRoute();
      case wellbeing:
        return wellbeingRoute();
      case schoolCards:
        return schoolCardsRoute();
      case consultations:
        return consultationsRoute(routeSettings.arguments);
      case activityGroups:
        return activityGroupsRoute();
      case schoolMessages:
        return schoolMessagesRoute(routeSettings.arguments);
      case academicContent:
        return academicContentRoute(routeSettings.arguments);
      case academicReports:
        return academicReportsRoute();
      case academicMarks:
        return academicMarksRoute();
      case mySchool:
        return mySchoolRoute();

      case profileDetails:
        return profileDetailsRoute();

      case handles:
        return handlesRoute();
      case profilePicture:
        return profilePictureRoute();
      case teacherAttendence:
        return TeacherAttendenceScreen.route(routeSettings);
      default:
        {
          return AdaptivePageRoute(builder: (context) => const Scaffold());
        }
    }
  }
}
