import 'dart:io';

import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/sessionWatcher.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:eschool_teacher/ui/widgets/downloadFileButton.dart';
import 'package:eschool_teacher/utils/errorMessageKeysAndCodes.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_teacher/cubits/appLocalizationCubit.dart';
import 'package:eschool_teacher/cubits/authCubit.dart';
import 'package:eschool_teacher/cubits/chat/chatUsersCubit.dart';
import 'package:eschool_teacher/cubits/dashboardCubit.dart';
import 'package:eschool_teacher/data/repositories/authRepository.dart';
import 'package:eschool_teacher/data/repositories/chatRepository.dart';
import 'package:eschool_teacher/data/repositories/settingsRepository.dart';
import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:eschool_teacher/utils/appLanguages.dart';
import 'package:eschool_teacher/utils/hiveBoxKeys.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool_teacher/utils/notificationConfig.dart';
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
import 'package:path_provider/path_provider.dart';

Future<void> initializeApp() async {
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

  await Firebase.initializeApp();

  // Pass all uncaught "fatal" errors from the framework to Crashlytics
  FlutterError.onError = FirebaseCrashlytics.instance.recordFlutterFatalError;

  // Pass all uncaught asynchronous errors that aren't handled by the Flutter framework to Crashlytics
  PlatformDispatcher.instance.onError = (error, stack) {
    FirebaseCrashlytics.instance.recordError(error, stack, fatal: true);
    return true;
  };

  NotificationUtility.configure(notificationConfig, session: Api.session);
  await NotificationUtility.initializeAwesomeNotification();

  SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
  await initHiveStorage([authBoxKey, settingsBoxKey]);
  await Hive.openBox(authBoxKey);
  await Hive.openBox(settingsBoxKey);
  runApp(const MyApp());
}

/// Starts Hive in the app's private support directory. Hive.initFlutter() would
/// use the Documents directory, which iOS shows in the Files app (because
/// UIFileSharingEnabled is on), exposing the boxes, including the login token.
Future<void> initHiveStorage(List<String> boxNames) async {
  final Directory hiveDirectory = await getApplicationSupportDirectory();
  await _moveHiveBoxesFromDocuments(hiveDirectory, boxNames);
  Hive.init(hiveDirectory.path);
}

/// Older versions kept the boxes in the Documents directory. Move them over
/// (best effort) so that updating the app does not sign anybody out.
Future<void> _moveHiveBoxesFromDocuments(
  Directory hiveDirectory,
  List<String> boxNames,
) async {
  final Directory documentsDirectory;
  try {
    documentsDirectory = await getApplicationDocumentsDirectory();
    if (documentsDirectory.path == hiveDirectory.path) {
      return;
    }
    await hiveDirectory.create(recursive: true);
  } catch (_) {
    return;
  }

  for (final boxName in boxNames) {
    //Hive stores every box under its lower-case name
    final String name = boxName.toLowerCase();
    try {
      if (await File('${hiveDirectory.path}/$name.hive').exists()) {
        continue;
      }
      for (final fileExtension in ['hive', 'hivec', 'lock']) {
        final File oldFile = File(
          '${documentsDirectory.path}/$name.$fileExtension',
        );
        if (!await oldFile.exists()) {
          continue;
        }
        final String newPath = '${hiveDirectory.path}/$name.$fileExtension';
        try {
          await oldFile.rename(newPath);
        } catch (_) {
          await oldFile.copy(newPath);
          await oldFile.delete();
        }
      }
    } catch (_) {
      //Could not move this box: it starts empty in the new directory
    }
  }
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
            return GestureDetector(
              behavior: HitTestBehavior.opaque,
              onTap: () {
                FocusManager.instance.primaryFocus?.unfocus();
              },
              child: MaterialApp(
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
                          statusBarIconBrightness: Brightness.light,
                          statusBarBrightness: Brightness.dark,
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
                            bottom: Platform.isAndroid,
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
                supportedLocales: appLanguages.map((language) {
                  return UiUtils.getLocaleFromLanguageCode(
                    language.languageCode,
                  );
                }).toList(),
                debugShowCheckedModeBanner: false,
                initialRoute: Routes.splash,
                onGenerateRoute: Routes.onGenerateRouted,
              ),
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
