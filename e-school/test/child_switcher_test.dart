import 'dart:convert';

import 'package:eschool/app/selectedChild.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/data/models/parent.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool/data/repositories/authRepository.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

Map<String, Object?> _student(int id, String firstName) => {
  'id': id,
  'user_id': id + 100,
  'first_name': firstName,
  'last_name': 'Mokoena',
  'image': '',
  'class_section_name': 'Grade 7 A',
};

/// A child as /me/context lists them (the generated Child model).
Map<String, Object?> _contextChild(
  int id,
  String firstName,
  int schoolId,
  String schoolName,
) => {
  'id': id,
  'user_id': id + 100,
  'first_name': firstName,
  'last_name': 'Mokoena',
  'name': '$firstName Mokoena',
  'image': null,
  'school': {
    'id': schoolId,
    'name': schoolName,
    'short_name': null,
    'crest_url': null,
  },
};

/// A signed-in parent of Thandi and Sipho, without Hive.
class _SignedInParent extends AuthRepository {
  _SignedInParent({this.isStudent = false});

  final bool isStudent;

  @override
  bool getIsLogIn() => true;

  @override
  bool getIsStudentLogIn() => isStudent;

  @override
  String getJwtToken() => 'token';

  @override
  Parent getParentDetails() => Parent.fromJson({
    'id': 21,
    'first_name': 'Lerato',
    'last_name': 'Mokoena',
    'children': [_student(11, 'Thandi'), _student(12, 'Sipho')],
  });

  @override
  Student getStudentDetails() => Student.fromJson(_student(11, 'Thandi'));
}

class _NoContext implements MeContextRepository {
  _NoContext(this.answer);

  final MeContext? answer;

  @override
  Future<MeContext?> fetchContext() async => answer;
}

/// A child screen: what it shows, and how often it was built afresh.
class _ChildScreen extends StatefulWidget {
  const _ChildScreen(this.child);

  final Student? child;
  static int created = 0;

  @override
  State<_ChildScreen> createState() => _ChildScreenState();
}

class _ChildScreenState extends State<_ChildScreen> {
  @override
  void initState() {
    super.initState();
    _ChildScreen.created++;
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    body: Center(child: Text('Showing ${widget.child?.firstName ?? 'me'}')),
  );
}

void main() {
  late ChildSelectionCubit selection;
  late MeContextCubit meContext;

  Future<void> pump(
    WidgetTester tester, {
    Size size = const Size(390, 844),
    bool isStudent = false,
  }) async {
    tester.view.physicalSize = size;
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);
    final TestDefaultBinaryMessenger messenger =
        tester.binding.defaultBinaryMessenger;
    rootBundle.evict('assets/languages/en.json');
    messenger.setMockMessageHandler('flutter/assets', (message) async {
      if (utf8.decode(message!.buffer.asUint8List()) ==
          'assets/languages/en.json') {
        return ByteData.sublistView(
          utf8.encode(
            jsonEncode({
              'switchChild': 'Switch child',
              'chooseChild': 'Choose a child',
              'yourChildren': 'Your children',
            }),
          ),
        );
      }
      return messenger.delegate.send('flutter/assets', message);
    });
    addTearDown(() => messenger.setMockMessageHandler('flutter/assets', null));
    _ChildScreen.created = 0;
    final AuthCubit auth = AuthCubit(_SignedInParent(isStudent: isStudent));
    await tester.pumpWidget(
      MultiBlocProvider(
        providers: [
          BlocProvider.value(value: auth),
          BlocProvider.value(value: selection),
          BlocProvider.value(value: meContext),
          BlocProvider(
            create: (_) => AppConfigurationCubit(SystemRepository()),
          ),
        ],
        child: MaterialApp(
          theme: AppTheme.light(),
          locale: const Locale('en'),
          supportedLocales: const [Locale('en')],
          localizationsDelegates: const [
            AppLocalizationDelegate([
              AppLanguage(languageCode: 'en', languageName: 'English'),
            ]),
          ],
          home: Builder(
            builder: (context) {
              if (!isStudent) {
                selection.setChildren(switcherChildren(context));
              }
              return ChildSwitcherFrame(
                child: SelectedChildScope(
                  builder: (context, child) => _ChildScreen(child),
                ),
              );
            },
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
  }

  setUp(() {
    selection = ChildSelectionCubit(store: InMemorySelectionStore());
    meContext = MeContextCubit(
      repository: _NoContext(null),
      cache: InMemoryMeContextCache(),
    );
  });

  tearDown(() async {
    await selection.close();
    await meContext.close();
  });

  testWidgets('a parent\'s child screen shows the child on show', (
    tester,
  ) async {
    await pump(tester);

    expect(find.text('Showing Thandi'), findsOneWidget);
    expect(find.byType(ChildSwitcherBar), findsOneWidget);
  });

  testWidgets('changing child once rebuilds the screen for the other child', (
    tester,
  ) async {
    await pump(tester);

    await tester.tap(find.byType(ChildSwitcherBar));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Sipho Mokoena').last);
    await tester.pumpAndSettle();

    expect(find.text('Showing Sipho'), findsOneWidget);
    expect(_ChildScreen.created, 2);
    expect(selection.state.selectedId, 12);
  });

  testWidgets('on a desktop the children are listed beside the screen', (
    tester,
  ) async {
    await pump(tester, size: const Size(1280, 800));

    expect(find.byType(ChildSwitcherRail), findsOneWidget);
    await tester.tap(find.text('Sipho Mokoena'));
    await tester.pumpAndSettle();

    expect(find.text('Showing Sipho'), findsOneWidget);
  });

  testWidgets('a learner sees their own screen, with no switcher', (
    tester,
  ) async {
    await pump(tester, isStudent: true);

    expect(find.text('Showing me'), findsOneWidget);
    expect(find.byType(ChildSwitcherBar), findsNothing);
  });

  testWidgets('the children come from /me/context when the server sends them', (
    tester,
  ) async {
    meContext = MeContextCubit(
      repository: _NoContext(
        MeContext.fromJson({
          'children': [
            _contextChild(12, 'Sipho', 2, 'Riverside Primary'),
            {
              ..._contextChild(30, 'Ayanda', 1, 'Northcliff High'),
              'homeroom': 'Grade 4 C',
            },
          ],
        }),
      ),
      cache: InMemoryMeContextCache(),
    );
    await meContext.load();
    await pump(tester);

    expect(selection.state.children.map((child) => child.id), [12, 30]);
    expect(find.text('Riverside Primary'), findsOneWidget);
    expect(find.text('Showing Sipho'), findsOneWidget);

    // A child the v1 sign-in does not list still opens, from the context.
    await selection.select(30);
    await tester.pumpAndSettle();
    expect(find.text('Showing Ayanda'), findsOneWidget);
  });
}
