import 'dart:io';

import 'package:eschool/app/appLocalization.dart';
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
import 'package:eschool/cubits/gallery/galleryAlbumsCubit.dart';
import 'package:eschool/cubits/gallery/galleryVideosCubit.dart';
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
import 'package:eschool/ui/styles/colors.dart';
import 'package:eschool/utils/appLanguages.dart';
import 'package:eschool/utils/hiveBoxKeys.dart';
import 'package:eschool/utils/notificationUtils/generalNotificationUtility.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_crashlytics/firebase_crashlytics.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:hive_flutter/hive_flutter.dart';

//to avoid handshake error on some devices
class MyHttpOverrides extends HttpOverrides {
  @override
  HttpClient createHttpClient(SecurityContext? context) {
    return super.createHttpClient(context)
      ..badCertificateCallback =
          (X509Certificate cert, String host, int port) => true;
  }
}

Future<void> initializeApp() async {
  WidgetsFlutterBinding.ensureInitialized();
  HttpOverrides.global = MyHttpOverrides();

  //Register the license of font
  LicenseRegistry.addLicense(() async* {
    final license = await rootBundle.loadString('google_fonts/OFL.txt');
    yield LicenseEntryWithLineBreaks(['google_fonts'], license);
  });

  SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);

  await Firebase.initializeApp();

  // Pass all uncaught "fatal" errors from the framework to Crashlytics
  FlutterError.onError = FirebaseCrashlytics.instance.recordFlutterFatalError;

  // Pass all uncaught asynchronous errors that aren't handled by the Flutter framework to Crashlytics
  PlatformDispatcher.instance.onError = (error, stack) {
    FirebaseCrashlytics.instance.recordError(error, stack, fatal: true);
    return true;
  };

  await NotificationUtility.initializeAwesomeNotification();

  await Hive.initFlutter();
  await Hive.openBox(showCaseBoxKey);
  await Hive.openBox(authBoxKey);

  await Hive.openBox(settingsBoxKey);

  runApp(const MyApp());
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
          create: (context) => GalleryAlbumCubit(),
        ),
        BlocProvider<GalleryVideosCubit>(
          create: (context) => GalleryVideosCubit(),
        ),
      ],
      child: Builder(
        builder: (context) {
          final currentLanguage = context
              .watch<AppLocalizationCubit>()
              .state
              .language;

          return MaterialApp(
            navigatorKey: UiUtils.rootNavigatorKey,
            theme: Theme.of(context).copyWith(
              primaryColor: primaryColor,
              textTheme: GoogleFonts.poppinsTextTheme(
                Theme.of(context).textTheme,
              ),
              scaffoldBackgroundColor: pageBackgroundColor,
              colorScheme: Theme.of(context).colorScheme.copyWith(
                primary: primaryColor,
                onPrimary: onPrimaryColor,
                secondary: secondaryColor,
                surface: surfaceColor,
                error: errorColor,
                onSecondary: onSecondaryColor,
                onSurface: onSurfaceColor,
              ),
            ),
            builder: (context, widget) {
              return ScrollConfiguration(
                behavior: GlobalScrollBehavior(),
                child: AnnotatedRegion<SystemUiOverlayStyle>(
                  value: SystemUiOverlayStyle.light.copyWith(
                    statusBarColor: Colors.transparent,
                    statusBarBrightness: Brightness.dark,
                    statusBarIconBrightness: Brightness.light,
                    systemNavigationBarContrastEnforced: false,
                    systemNavigationBarColor: pageBackgroundColor,
                    systemNavigationBarIconBrightness: Brightness.dark,
                  ),
                  child: ColoredBox(
                    color: pageBackgroundColor,
                    child: SafeArea(
                      top: false,
                      bottom: Platform.isAndroid,
                      child: widget!,
                    ),
                  ),
                ),
              );
            },
            locale: currentLanguage,
            localizationsDelegates: const [
              AppLocalization.delegate,
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
            onGenerateRoute: Routes.onGenerateRouted,
          );
        },
      ),
    );
  }
}
