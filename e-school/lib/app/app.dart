import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool/app/sessionWatcher.dart';
import 'package:eschool/utils/api.dart';
import 'package:eschool/ui/widgets/downloadFileButton.dart';
import 'package:eschool/utils/errorMessageKeysAndCodes.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/appLocalizationCubit.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/chat/chatUsersCubit.dart';
import 'package:eschool/cubits/examDetailsCubit.dart';
import 'package:eschool/cubits/examTabSelectionCubit.dart';
import 'package:eschool/cubits/examsOnlineCubit.dart';
import 'package:eschool/cubits/feesPaymentCubit.dart';
import 'package:eschool/cubits/feesReceiptCubit.dart';
import 'package:eschool/cubits/noticeBoardCubit.dart';
import 'package:eschool/cubits/postFeesPaymentCubit.dart';
import 'package:eschool/cubits/resultTabSelectionCubit.dart';
import 'package:eschool/cubits/resultsOnlineCubit.dart';
import 'package:eschool/cubits/studentDashboardCubit.dart';
import 'package:eschool/cubits/studentFeesCubit.dart';
import 'package:eschool/data/repositories/announcementRepository.dart';
import 'package:eschool/data/repositories/authRepository.dart';
import 'package:eschool/data/repositories/chatRepository.dart';
import 'package:eschool/data/repositories/examRepository.dart';
import 'package:eschool/data/repositories/resultRepository.dart';
import 'package:eschool/data/repositories/settingsRepository.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:eschool/ui/screens/exam/onlineExam/cubits/examOnlineCubit.dart';
import 'package:eschool/utils/appLanguages.dart';
import 'package:eschool/utils/hiveBoxKeys.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool/utils/notificationConfig.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_crashlytics/firebase_crashlytics.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_web_plugins/url_strategy.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:hive_flutter/hive_flutter.dart';

Future<void> initializeApp() async {
  //a web build shows each screen's address without the '#'
  usePathUrlStrategy();
  await prepareApp();
  runApp(const MyApp());
}

/// Everything the app needs before its first frame. The integration smoke
/// flows pass [useFirebase] false: they run against a fake API, with no
/// Firebase project.
Future<void> prepareApp({bool useFirebase = true}) async {
  WidgetsFlutterBinding.ensureInitialized();

  //a debug build with a mistyped --dart-define stops here and says why
  AppConfig.assertValid();

  //debugPrint is not stripped from release builds, so silence it there
  if (kReleaseMode) {
    debugPrint = (String? message, {int? wrapWidth}) {};
  }

  //Register the license of font
  LicenseRegistry.addLicense(() async* {
    final license = await rootBundle.loadString('google_fonts/OFL.txt');
    yield LicenseEntryWithLineBreaks(['google_fonts'], license);
  });

  SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);

  //a browser has no Firebase app (no push, no Crashlytics): errors go to
  //the console there
  if (useFirebase && !kIsWeb) {
    await Firebase.initializeApp();

    // Pass all uncaught "fatal" errors from the framework to Crashlytics
    FlutterError.onError = FirebaseCrashlytics.instance.recordFlutterFatalError;

    // Pass all uncaught asynchronous errors that aren't handled by the Flutter framework to Crashlytics
    PlatformDispatcher.instance.onError = (error, stack) {
      FirebaseCrashlytics.instance.recordError(error, stack, fatal: true);
      return true;
    };
  }

  NotificationUtility.configure(notificationConfig, session: Api.session);
  await NotificationUtility.initializeAwesomeNotification();

  await initAppStorage([showCaseBoxKey, authBoxKey, settingsBoxKey]);
  await Hive.openBox(showCaseBoxKey);
  await Hive.openBox(authBoxKey);

  await Hive.openBox(settingsBoxKey);
}

class GlobalScrollBehavior extends ScrollBehavior {
  @override
  ScrollPhysics getScrollPhysics(BuildContext context) {
    return const BouncingScrollPhysics();
  }
}

class MyApp extends StatefulWidget {
  const MyApp({super.key});

  @override
  State<MyApp> createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> {
  @override
  Widget build(BuildContext context) {
    return MultiBlocProvider(
      providers: [
        BlocProvider<MeContextCubit>(
          create: (_) => MeContextCubit(
            repository: ApiMeContextRepository(Api.client),
            cache: HiveMeContextCache(),
          ),
        ),
        BlocProvider<AppLocalizationCubit>(
          create: (_) => AppLocalizationCubit(SettingsRepository()),
        ),
        BlocProvider<AuthCubit>(create: (_) => AuthCubit(AuthRepository())),
        BlocProvider<StudentDashboardCubit>(
          create: (_) =>
              StudentDashboardCubit(studentRepository: StudentRepository()),
        ),
        BlocProvider<NoticeBoardCubit>(
          create: (context) => NoticeBoardCubit(AnnouncementRepository()),
        ),
        BlocProvider<AppConfigurationCubit>(
          create: (context) => AppConfigurationCubit(SystemRepository()),
        ),
        BlocProvider<ExamDetailsCubit>(
          create: (context) => ExamDetailsCubit(StudentRepository()),
        ),
        BlocProvider<StudentFeesCubit>(
          create: (context) => StudentFeesCubit(StudentRepository()),
        ),
        BlocProvider<FeesPaymentCubit>(
          create: (context) => FeesPaymentCubit(StudentRepository()),
        ),
        BlocProvider<PostFeesPaymentCubit>(
          create: (context) => PostFeesPaymentCubit(StudentRepository()),
        ),
        BlocProvider<FeesReceiptCubit>(
          create: (context) => FeesReceiptCubit(StudentRepository()),
        ),
        BlocProvider<ResultTabSelectionCubit>(
          create: (_) => ResultTabSelectionCubit(),
        ),

        BlocProvider<ExamTabSelectionCubit>(
          create: (_) => ExamTabSelectionCubit(),
        ),
        BlocProvider<ExamOnlineCubit>(
          create: (_) => ExamOnlineCubit(ExamOnlineRepository()),
        ),
        BlocProvider<ExamsOnlineCubit>(
          create: (_) => ExamsOnlineCubit(ExamOnlineRepository()),
        ),
        BlocProvider<ResultsOnlineCubit>(
          create: (_) => ResultsOnlineCubit(ResultOnlineRepository()),
        ),
        BlocProvider<ChatUsersCubit>(
          create: (context) => ChatUsersCubit(ChatRepository()),
        ),
        BlocProvider<GalleryAlbumCubit>(
          create: (context) => GalleryAlbumCubit(SystemRepository()),
        ),
        BlocProvider<GalleryVideosCubit>(
          create: (context) => GalleryVideosCubit(SystemRepository()),
        ),
      ],
      child: SessionWatcher(
        child: Builder(
          builder: (context) {
            final currentLanguage = context
                .watch<AppLocalizationCubit>()
                .state
                .language;

            return MaterialApp(
              navigatorKey: UiUtils.rootNavigatorKey,
              theme: AppTheme.light(font: GoogleFonts.poppinsTextTheme),
              builder: (context, widget) {
                return AppShell(
                  errorMessageKeyFromCode:
                      ErrorMessageKeysAndCode.getErrorMessageKeyFromCode,
                  openStudyMaterial: _openStudyMaterial,
                  studyMaterialButton: _studyMaterialButton,
                  bottomNavigationHeightPercentage:
                      UiUtils.bottomNavigationHeightPercentage,
                  child: ScrollConfiguration(
                    behavior: GlobalScrollBehavior(),
                    child: AnnotatedRegion<SystemUiOverlayStyle>(
                      value: SystemUiOverlayStyle.light.copyWith(
                        statusBarColor: Colors.transparent,
                        statusBarBrightness: Brightness.dark,
                        statusBarIconBrightness: Brightness.light,
                        systemNavigationBarContrastEnforced: false,
                        systemNavigationBarColor: Theme.of(
                          context,
                        ).colorScheme.surface,
                        systemNavigationBarIconBrightness: Brightness.dark,
                      ),
                      child: ColoredBox(
                        color: Theme.of(context).colorScheme.surface,
                        child: SafeArea(
                          top: false,
                          bottom: _isAndroid,
                          child: widget!,
                        ),
                      ),
                    ),
                  ),
                );
              },
              locale: currentLanguage,
              localizationsDelegates: const [
                AppLocalizationDelegate(appLanguages),
                GlobalMaterialLocalizations.delegate,
                GlobalWidgetsLocalizations.delegate,
                GlobalCupertinoLocalizations.delegate,
              ],
              supportedLocales: appLanguages.map((appLanguage) {
                return UiUtils.getLocaleFromLanguageCode(
                  appLanguage.languageCode,
                );
              }).toList(),
              debugShowCheckedModeBanner: false,
              initialRoute: Routes.splash,
              onGenerateInitialRoutes: kIsWeb ? _webInitialRoutes : null,
              onGenerateRoute: Routes.onGenerateRouted,
            );
          },
        ),
      ),
    );
  }
}

/// How the shared widgets in eschool_core open an attachment in this app.
void _openStudyMaterial(BuildContext context, StudyMaterial studyMaterial) {
  UiUtils.viewOrDownloadStudyMaterial(
    context: context,
    storeInExternalStorage: true,
    studyMaterial: studyMaterial,
  );
}

Widget _studyMaterialButton(
  BuildContext context,
  StudyMaterial studyMaterial,
) => DownloadFileButton(studyMaterial: studyMaterial);

/// Android draws the app behind its navigation bar, so the bottom edge needs
/// the safe area there (and only there, as before).
bool get _isAndroid =>
    !kIsWeb && defaultTargetPlatform == TargetPlatform.android;

/// A browser opens the app at whatever address is in the address bar, but the
/// screens behind those addresses need arguments an address does not carry:
/// every visit starts at the splash screen, which signs in and goes home.
/// Links the app understands still open their screen from there (see
/// deepLinks.dart).
List<Route<dynamic>> _webInitialRoutes(String _) => [
  Routes.onGenerateRouted(const RouteSettings(name: Routes.splash)),
];
