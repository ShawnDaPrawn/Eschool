import 'package:eschool/ui/screens/fees/studentFeePaymentDueScreen.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/app/deepLinks.dart';
import 'package:eschool/app/sharedScreens.dart';
import 'package:eschool/ui/screens/aboutUsScreen.dart';
import 'package:eschool/ui/screens/academicCalendar/academicCalendarScreen.dart';
import 'package:eschool/ui/screens/assignment/assignmentScreen.dart';
import 'package:eschool/ui/screens/auth/authScreen.dart';
import 'package:eschool/ui/screens/auth/parentLoginScreen.dart';
import 'package:eschool/ui/screens/auth/parentPolicyScreen.dart';
import 'package:eschool/ui/screens/auth/studentLoginScreen.dart';
import 'package:eschool/ui/screens/chapterDetails/chapterDetailsScreen.dart';
import 'package:eschool/ui/screens/chat/chatMessagesScreen.dart';
import 'package:eschool/ui/screens/chat/chatUserProfileScreen.dart';
import 'package:eschool/ui/screens/chat/chatUserSearchScreen.dart';
import 'package:eschool/ui/screens/chat/chatUsersScreen.dart';
import 'package:eschool/ui/screens/childAssignmentsScreen.dart';
import 'package:eschool/ui/screens/childAttendanceScreen.dart';
import 'package:eschool/ui/screens/childDetailsScreen.dart';
import 'package:eschool/ui/screens/childResultsScreen.dart';
import 'package:eschool/ui/screens/childTeachers.dart';
import 'package:eschool/ui/screens/childTimeTableScreen.dart';
import 'package:eschool/ui/screens/contactUsScreen.dart';
import 'package:eschool/ui/screens/event/eventsDetailsScreen.dart';
import 'package:eschool/ui/screens/exam/examTimeTableScreen.dart';
import 'package:eschool/ui/screens/exam/onlineExam/examOnlineScreen.dart';
import 'package:eschool/ui/screens/examScreen.dart';
import 'package:eschool/ui/screens/fileViews/imageFileScreen.dart';
import 'package:eschool/ui/screens/fileViews/pdfFileScreen.dart';
import 'package:eschool/ui/screens/gallery/galleryAlbumPhotosScreen.dart';
import 'package:eschool/ui/screens/gallery/galleryScreen.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool/ui/screens/leave/addLeaveScreen.dart';
import 'package:eschool/ui/screens/leave/manageLeavesScreen.dart';
import 'package:eschool/ui/screens/noticeBoardScreen.dart';
import 'package:eschool/ui/screens/notificationsScreen.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool/ui/screens/parentProfileScreen.dart';
import 'package:eschool/ui/screens/playVideo/playVideoScreen.dart';
import 'package:eschool/ui/screens/privacyPolicyScreen.dart';
import 'package:eschool/ui/screens/reports/reportsScreen.dart';
import 'package:eschool/ui/screens/resultOnline/resultOnlineScreen.dart';
import 'package:eschool/ui/screens/resultScreen.dart';
import 'package:eschool/ui/screens/selectSubjectsScreen.dart';
import 'package:eschool/ui/screens/settingsScreen.dart';
import 'package:eschool/ui/screens/splashScreen.dart';
import 'package:eschool/ui/screens/studentProfileScreen.dart';
import 'package:eschool/ui/screens/subjectDetails/subjectDetailsScreen.dart';
import 'package:eschool/ui/screens/termsAndConditionScreen.dart';
import 'package:eschool/ui/screens/topicDetailsScreen.dart';
import 'package:flutter/material.dart';

// ignore: avoid_classes_with_only_static_members
class Routes {
  static const String personalWallets = '/wallets';
  static const String splash = 'splash';
  static const String home = '/';

  static const String auth = 'auth';
  static const String parentLogin = 'parentLogin';
  static const String parentPolicies = 'parentPolicies';
  static const String studentLogin = 'studentLogin';
  static const String studentProfile = '/studentProfile';
  static const String assignment = '/assignment';

  static const String exam = '/exam';

  static const String examTimeTable = '/examTimeTable';

  static const String subjectDetails = '/subjectDetails';

  static const String chapterDetails = '/chapterDetails';

  static const String aboutUs = '/aboutUs';
  static const String privacyPolicy = '/privacyPolicy';

  static const String contactUs = '/contactUs';

  static const String termsAndCondition = '/termsAndCondition';

  static const String selectSubjects = '/selectSubjects';
  static const String result = '/result';
  static const String parentHome = 'parent/';

  static const String studentDetails = 'parent/studentDetails';

  static const String topicDetails = '/topicDetails';

  static const String playVideo = '/playVideo';

  static const String childAssignments = '/childAssignments';

  static const String childAttendance = '/childAttendance';

  static const String childTimeTable = '/childTimeTable';

  static const String childResults = '/childResults';

  static const String childTeachers = '/childTeachers';

  static const String settings = '/settings';

  static const String parentProfile = '/parentProfile';

  static const String noticeBoard = '/noticeBoard';

  static const String academicCalendar = '/calendar';

  static const String childFees = '/fees';

  static const String feeStatements = '/fee-statements';

  static const String feesDetails = '/feesDetails';

  static const String paymentVerify = '/paymentVerify';

  static const String subjectWiseReport = '/reportSubjectsContainer';

  static const String examOnline = '/examOnline';

  static const String resultOnline = '/resultOnline';

  static const String feesTransaction = '/feesTransaction';

  static const String notifications = '/notifications';

  static const String pdfFileView = '/pdfFileView';
  static const String imageFileView = '/imageFileView';

  static const String chatMessages = '/chatMessages';
  static const String chatUserPage = '/chatUserPage';
  static const String chatUserProfile = '/chatUserProfile';
  static const String chatUserSearch = '/chatUserSearch';

  static const String eventDetails = '/eventDetails';

  static const String studentFeePaymentDueScreen = '/studentFeePaymentDue';
  static const String webViewPaymentScreen = '/webViewPayment';

  static const String manageLeaves = '/manageLeaves';
  static const String addLeave = '/addLeave';

  //the screens built once in the shared packages (sharedScreens.dart)
  static const String notificationSettings = '/notifications/settings';
  static const String mySchool = '/my-school';
  static const String academicMarks = '/academics/marks';
  static const String academicReports = '/academics/reports';
  static const String academicContent = '/academics/content';
  static const String mastery = '/growth/mastery';
  static const String weeklyDigest = '/learning/weekly-digest';
  static const String tutor = '/learning/tutor';
  static const String tutorSummary = '/learning/tutor-summary';
  static const String learnerDiary = '/learning/diary';
  static const String examCoach = '/learning/exam-coach';
  static const String dailyPractice = '/learning/practice';
  static const String familyDiscipline = '/discipline';
  static const String familyProgress = '/growth/progress';
  static const String reportConcern = '/report-concern';
  static const String privateGallery = '/community/private-gallery';
  static const String recognition = '/community/recognition';
  static const String schoolTransfers = '/academics/transfers';
  static const String familyForms = '/family-forms';
  static const String familyVisits = '/family-visits';
  static const String peerTutoring = '/peer-tutoring';
  static const String pathway = '/pathways';
  static const String schoolAssistance = '/ask-school';
  static const String schoolCards = '/school-cards';
  static const String consultations = '/consultations';
  static const String activityGroups = '/activities/groups';
  static const String schoolMessages = '/communication/messages';
  static const String academicTimetable = '/academics/timetable';
  static const String academicAttendance = '/academics/attendance';
  static const String approvals = '/approvals';
  static const String invitation = '/invite';
  static const String profilePicture = '/profile-picture';
  static const String profileDetails = '/profile-details';
  static const String handles = '/handles';

  static const String gallery = SharedRoutes.gallery;
  static const String galleryAlbumPhotos = SharedRoutes.galleryAlbumPhotos;

  static String currentRoute = splash;

  static Route<dynamic> onGenerateRouted(RouteSettings routeSettings) {
    final route = _buildRoute(routeSettings);
    final module = switch (routeSettings.name) {
      schoolTransfers => 'school_transfers',
      familyDiscipline => 'discipline',
      familyProgress => 'academics',
      reportConcern => 'wellbeing',
      mastery => 'mastery',
      consultations => 'consultations',
      familyForms => 'family_forms',
      familyVisits => 'family_visits',
      peerTutoring => 'peer_tutoring',
      pathway => 'pathway',
      schoolAssistance => 'ask_school',
      personalWallets => 'wallet',
      schoolCards => 'cards',
      tutor || tutorSummary => 'tutor',
      learnerDiary => 'diary',
      examCoach => 'exam_coach',
      dailyPractice => 'daily_practice',
      activityGroups => 'groups',
      schoolMessages => 'communication',
      chatUserPage ||
      chatMessages ||
      chatUserProfile ||
      chatUserSearch => 'chat',
      examOnline || resultOnline => 'online_exams',
      feeStatements ||
      childFees ||
      feesDetails ||
      feesTransaction ||
      paymentVerify ||
      webViewPaymentScreen => 'fees',
      _ => null,
    };
    return module == null
        ? route
        : guardModuleRoute(
            route,
            module,
            write: routeSettings.name == webViewPaymentScreen,
          );
  }

  static Route<dynamic> _buildRoute(RouteSettings routeSettings) {
    currentRoute = routeSettings.name ?? '';

    //a link that opened the app, or came before sign-in, opens over home
    if (currentRoute == home || currentRoute == parentHome) {
      openPendingDeepLink();
    }
    //an invitation link opens before sign-in
    if (currentRoute == auth) {
      openPendingPublicDeepLink();
    }

    debugPrint('Route: $currentRoute');

    switch (routeSettings.name) {
      case personalWallets:
        return personalWalletsRoute();
      case splash:
        return SplashScreen.route(routeSettings);

      case auth:
        return AdaptivePageRoute(builder: (_) => const AuthScreen());

      case studentLogin:
        return StudentLoginScreen.route(routeSettings);

      case parentLogin:
        return ParentLoginScreen.route(routeSettings);

      case parentPolicies:
        return ParentPolicyScreen.route(routeSettings);

      case home:
        return HomeScreen.route(routeSettings);

      case parentHome:
        return ParentHomeScreen.route(routeSettings);

      case assignment:
        return AssignmentScreen.route(routeSettings);

      case exam:
        return ExamScreen.route(routeSettings);

      case examTimeTable:
        return ExamTimeTableScreen.route(routeSettings);

      case subjectDetails:
        return SubjectDetailsScreen.route(routeSettings);

      case chapterDetails:
        return ChapterDetailsScreen.route(routeSettings);

      case studentProfile:
        return StudentProfileScreen.route(routeSettings);

      case aboutUs:
        return AboutUsScreen.route(routeSettings);

      case privacyPolicy:
        return PrivacyPolicyScreen.route(routeSettings);

      case result:
        return ResultScreen.route(routeSettings);

      case selectSubjects:
        return SelectSubjectsScreen.route(routeSettings);

      case studentDetails:
        return ChildDetailsScreen.route(routeSettings);

      case topicDetails:
        return TopicDetailsScreen.route(routeSettings);

      case playVideo:
        return PlayVideoScreen.route(routeSettings);

      case childAssignments:
        return ChildAssignmentsScreen.route(routeSettings);

      case childAttendance:
        return ChildAttendanceScreen.route(routeSettings);

      case childTimeTable:
        return ChildTimeTableScreen.route(routeSettings);

      case childResults:
        return ChildResultsScreen.route(routeSettings);

      case childTeachers:
        return ChildTeachersScreen.route(routeSettings);

      case settings:
        return AdaptivePageRoute(builder: (_) => const SettingsScreen());

      case parentProfile:
        return AdaptivePageRoute(builder: (_) => const ParentProfileScreen());

      case noticeBoard:
        return NoticeBoardScreen.route(routeSettings);

      case contactUs:
        return ContactUsScreen.route(routeSettings);

      case termsAndCondition:
        return TermsAndConditionScreen.route(routeSettings);

      case academicCalendar:
        return AcademicCalendarScreen.route(routeSettings);

      case feeStatements:
        return feeStatementsRoute();

      case childFees:
        return feeStatementsRoute();

      case feesDetails:
        return feeStatementsRoute();

      case resultOnline:
        return ResultOnlineScreen.route(routeSettings);

      case feesTransaction:
        return feeStatementsRoute();

      case notifications:
        return NotificationScreen.route(routeSettings);
      case pdfFileView:
        return PdfFileScreen.route(routeSettings);
      case imageFileView:
        return ImageFileScreen.route(routeSettings);
      case paymentVerify:
        return feeStatementsRoute();

      case subjectWiseReport:
        return ReportsScreen.route(routeSettings);

      case examOnline:
        return ExamOnlineScreen.route(routeSettings);

      case chatUserPage:
        return AdaptivePageRoute(
          settings: routeSettings,
          builder: (_) => const ChatUsersScreen(hasBack: true),
        );
      case chatMessages:
        return ChatMessagesScreen.route(routeSettings);
      case chatUserProfile:
        return ChatUserProfileScreen.route(routeSettings);

      case chatUserSearch:
        return ChatUsersSearchScreen.route(routeSettings);

      case eventDetails:
        return EventDetailsScreen.route(routeSettings);

      case studentFeePaymentDueScreen:
        return StudentFeePaymentDueScreen.route(routeSettings);

      case webViewPaymentScreen:
        return feeStatementsRoute();

      case addLeave:
        return AddLeaveScreen.route(routeSettings);

      case manageLeaves:
        return ManageLeavesScreen.route(routeSettings);

      case gallery:
        return GalleryScreen.route(routeSettings);

      case galleryAlbumPhotos:
        return GalleryAlbumPhotosScreen.route(routeSettings);

      case notificationSettings:
        return notificationSettingsRoute();

      case academicAttendance:
        return academicAttendanceRoute();
      case academicTimetable:
        return academicTimetableRoute();
      case privateGallery:
        return familyPrivateGalleryRoute();
      case recognition:
        return familyRecognitionRoute();
      case schoolTransfers:
        return schoolTransfersRoute();

      case familyDiscipline:
        return familyDisciplineRoute();
      case familyProgress:
        return familyProgressRoute();
      case reportConcern:
        return reportConcernRoute();
      case weeklyDigest:
        return familyWeeklyDigestRoute();
      case tutor:
        return tutorRoute(summary: false);
      case tutorSummary:
        return tutorRoute(summary: true);
      case learnerDiary:
        return learnerDiaryRoute();
      case examCoach:
        return examCoachRoute();
      case dailyPractice:
        return dailyPracticeRoute();
      case mastery:
        return masteryRoute();
      case pathway:
        return pathwayRoute();
      case peerTutoring:
        return peerTutoringRoute();
      case schoolAssistance:
        return schoolAssistanceRoute();
      case familyVisits:
        return familyVisitsRoute();
      case familyForms:
        return familyFormsRoute();
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

      case approvals:
        return approvalsRoute();

      case invitation:
        return invitationRoute(routeSettings.arguments as String? ?? '');

      case profileDetails:
        return profileDetailsRoute();

      case handles:
        return handlesRoute();
      case profilePicture:
        return profilePictureRoute();

      default:
        return AdaptivePageRoute(builder: (context) => const Scaffold());
    }
  }
}
