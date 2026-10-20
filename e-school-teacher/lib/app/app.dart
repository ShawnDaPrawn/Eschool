import 'dart:io';

import 'package:eschool_teacher/app/appLocalization.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_teacher/cubits/appLocalizationCubit.dart';
import 'package:eschool_teacher/cubits/authCubit.dart';
import 'package:eschool_teacher/cubits/chat/chatUsersCubit.dart';
import 'package:eschool_teacher/cubits/dashboardCubit.dart';
import 'package:eschool_teacher/cubits/gallery/galleryAlbumsCubit.dart';
import 'package:eschool_teacher/cubits/gallery/galleryVideosCubit.dart';
import 'package:eschool_teacher/data/repositories/authRepository.dart';
import 'package:eschool_teacher/data/repositories/chatRepository.dart';
import 'package:eschool_teacher/data/repositories/settingsRepository.dart';
import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:eschool_teacher/ui/styles/colors.dart';
import 'package:eschool_teacher/utils/appLanguages.dart';
import 'package:eschool_teacher/utils/hiveBoxKeys.dart';
import 'package:eschool_teacher/utils/notificationUtils/generalNotificationUtility.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
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

  await Firebase.initializeApp();

  // Pass all uncaught "fatal" errors from the framework to Crashlytics
  FlutterError.onError = FirebaseCrashlytics.instance.recordFlutterFatalError;

  // Pass all uncaught asynchronous errors that aren't handled by the Flutter framework to Crashlytics
  PlatformDispatcher.instance.onError = (error, stack) {
    FirebaseCrashlytics.instance.recordError(error, stack, fatal: true);
    return true;
  };

  await NotificationUtility.initializeAwesomeNotification();

  SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
  await Hive.initFlutter();
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
        BlocProvider<AppConfigurationCubit>(
          create: (_) => AppConfigurationCubit(SystemRepository()),
        ),
        BlocProvider<AppLocalizationCubit>(
          create: (_) => AppLocalizationCubit(SettingsRepository()),
        ),
        BlocProvider<AuthCubit>(create: (_) => AuthCubit(AuthRepository())),
        BlocProvider<DashboardCubit>(
          create: (_) => DashboardCubit(TeacherRepository()),
        ),
        BlocProvider<StudentChatUsersCubit>(
          create: (_) => StudentChatUsersCubit(ChatRepository()),
        ),
        BlocProvider<ParentChatUserCubit>(
          create: (_) => ParentChatUserCubit(ChatRepository()),
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
          return GestureDetector(
            behavior: HitTestBehavior.opaque,
            onTap: () {
              FocusManager.instance.primaryFocus?.unfocus();
            },
            child: MaterialApp(
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
                      statusBarIconBrightness: Brightness.light,
                      statusBarBrightness: Brightness.dark,
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
              supportedLocales: appLanguages.map((language) {
                return UiUtils.getLocaleFromLanguageCode(language.languageCode);
              }).toList(),
              debugShowCheckedModeBanner: false,
              initialRoute: Routes.splash,
              onGenerateRoute: Routes.onGenerateRouted,
            ),
          );
        },
      ),
    );
  }
}
