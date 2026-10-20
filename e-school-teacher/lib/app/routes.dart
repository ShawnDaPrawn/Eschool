import 'package:eschool_teacher/ui/screens/aboutUsScreen.dart';
import 'package:eschool_teacher/ui/screens/academicCalendar/academicCalendarScreen.dart';
import 'package:eschool_teacher/ui/screens/add&editAssignmentScreen.dart';
import 'package:eschool_teacher/ui/screens/addOrEditAnnouncementScreen.dart';
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
import 'package:eschool_teacher/ui/screens/teacherAttendence/teacherAttendenceScreen.dart';
import 'package:eschool_teacher/ui/screens/termsAndConditionScreen.dart';
import 'package:eschool_teacher/ui/screens/topcisByLessonScreen.dart';
import 'package:eschool_teacher/ui/screens/topicsScreen.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';

// ignore: avoid_classes_with_only_static_members
class Routes {
  static const String splash = 'splash';
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

  static const String gallery = '/gallery';
  static const String galleryAlbumPhotos = '/galleryAlbumPhotos';

  static const String teacherAttendence = '/teacherAttendence';

  static String currentRoute = splash;

  static Route<dynamic> onGenerateRouted(RouteSettings routeSettings) {
    currentRoute = routeSettings.name ?? '';

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
      case teacherAttendence:
        return TeacherAttendenceScreen.route(routeSettings);
      default:
        {
          return CupertinoPageRoute(builder: (context) => const Scaffold());
        }
    }
  }
}
