import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fixtures.dart';
import 'support/harness.dart';

class _Recorder implements HttpClientAdapter {
  final List<RequestOptions> requests = [];

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    requests.add(options);
    return ResponseBody.fromString(
      jsonEncode({'error': false, 'data': <Object>[]}),
      200,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

class _Contexts implements MeContextRepository {
  int calls = 0;

  @override
  Future<MeContext?> fetchContext() async {
    calls++;
    return MeContext.fromJson(phase1GuardianJson);
  }
}

final MeContext oneSchool = MeContext.fromJson(contextJson);
final MeContext twoSchools = MeContext.fromJson(phase1GuardianJson);

void main() {
  test(
    'finishing an old clear cannot erase replacement school context',
    () async {
      final subject = SchoolSelectionCubit(store: InMemorySelectionStore());
      await subject.syncWithContext(oneSchool);
      final cleared = subject.clear();
      expect(subject.state.schools, isEmpty);
      await subject.syncWithContext(twoSchools);
      await cleared;
      expect(subject.state.schools, twoSchools.schools);
      await subject.close();
    },
  );
  late InMemorySelectionStore store;
  late SchoolSelectionCubit cubit;

  setUp(() {
    store = InMemorySelectionStore();
    cubit = SchoolSelectionCubit(store: store);
  });

  tearDown(() => cubit.close());

  group('SchoolSelectionCubit', () {
    test('one school: nothing to switch, but the school is sent', () async {
      await cubit.syncWithContext(oneSchool);

      expect(cubit.state.canSwitch, isFalse);
      expect(cubit.state.selectedId, 1);
      expect(store.schoolId, 1);
    });

    test('a single school other than the default is sent too', () async {
      final Map<String, dynamic> json =
          jsonDecode(jsonEncode(phase1GuardianJson)) as Map<String, dynamic>;
      json['schools'] = [(json['schools'] as List).last];
      final MeContext onlySchoolTwo = MeContext.fromJson(json);
      final int only = onlySchoolTwo.schools.single.school.id;

      await cubit.syncWithContext(onlySchoolTwo);

      expect(cubit.state.canSwitch, isFalse);
      expect(store.schoolId, only);
    });

    test('several schools: the current one is chosen and sent', () async {
      await cubit.syncWithContext(twoSchools);

      expect(cubit.state.canSwitch, isTrue);
      expect(cubit.state.selected!.school.name, 'Riverside Primary');
      expect(store.schoolId, 2);
    });

    test('the school chosen last time is restored', () async {
      store.schoolId = 1;
      cubit = SchoolSelectionCubit(store: store);

      await cubit.syncWithContext(twoSchools);

      expect(cubit.state.selectedId, 1);
      expect(store.schoolId, 1);
    });

    test('a school the user left is no longer sent', () async {
      store.schoolId = 9;
      cubit = SchoolSelectionCubit(store: store);

      await cubit.syncWithContext(oneSchool);

      expect(store.schoolId, 1);
      expect(cubit.state.selectedId, 1);
    });

    test('an older server without schools changes nothing', () async {
      store.schoolId = 2;
      cubit = SchoolSelectionCubit(store: store);

      await cubit.syncWithContext(MeContext.fromJson(const {}));
      await cubit.syncWithContext(null);

      expect(store.schoolId, 2);
    });

    test('switching to another of the user\'s schools', () async {
      await cubit.syncWithContext(twoSchools);

      expect(await cubit.select(1), isTrue);
      expect(store.schoolId, 1);
      expect(await cubit.select(1), isFalse);
    });

    test('a school the user does not belong to is refused', () async {
      await cubit.syncWithContext(twoSchools);

      expect(await cubit.select(7), isFalse);
      expect(store.schoolId, 2);
    });

    test('a guardian follows their child to the child\'s school', () async {
      await cubit.syncWithContext(twoSchools);

      expect(await cubit.follow(1), isTrue);
      expect(store.schoolId, 1);
      expect(await cubit.follow(0), isFalse);
    });

    test(
      'with no school list yet, the child\'s school is still sent',
      () async {
        expect(await cubit.follow(3), isTrue);
        expect(store.schoolId, 3);
      },
    );

    test('sign-out forgets the schools and the choice', () async {
      await cubit.syncWithContext(twoSchools);

      await cubit.clear();

      expect(cubit.state.schools, isEmpty);
      expect(store.schoolId, isNull);
    });
  });

  group('SchoolHeaderInterceptor', () {
    Future<RequestOptions> send(
      int? school, {
      Map<String, dynamic>? headers,
    }) async {
      final _Recorder adapter = _Recorder();
      final Dio dio = withSchoolHeader(
        Dio()..httpClientAdapter = adapter,
        () => school,
      );
      await dio.get<dynamic>(
        'https://api.test/api/parent/subjects',
        options: Options(headers: headers),
      );
      return adapter.requests.single;
    }

    test('sends the chosen school with every call', () async {
      expect((await send(2)).headers['X-School-Id'], '2');
    });

    test('sends none before a school is chosen', () async {
      expect((await send(null)).headers.containsKey('X-School-Id'), isFalse);
    });

    test('every call says the app joins per-school push topics', () async {
      expect((await send(null)).headers['X-Push-Topics'], 'school');
      expect((await send(2)).headers['X-Push-Topics'], 'school');
    });

    test('a call that names its own school keeps it', () async {
      final RequestOptions request = await send(
        2,
        headers: {'x-school-id': '1'},
      );

      // Dio's headers ignore case: the request's own value stays.
      expect(request.headers['X-School-Id'], '1');
    });

    test('a store that cannot be read sends none', () async {
      final _Recorder adapter = _Recorder();
      final Dio dio = withSchoolHeader(
        Dio()..httpClientAdapter = adapter,
        () => throw StateError('box not open'),
      );
      await dio.get<dynamic>('https://api.test/api/settings');

      expect(
        adapter.requests.single.headers.containsKey('X-School-Id'),
        isFalse,
      );
    });
  });

  group('SchoolSwitcherButton', () {
    late MeContextCubit meContext;
    late _Contexts contexts;
    late int switched;

    Widget header() => MultiBlocProvider(
      providers: [
        BlocProvider.value(value: cubit),
        BlocProvider.value(value: meContext),
      ],
      child: Scaffold(
        appBar: AppBar(
          actions: [SchoolSwitcherButton(onSwitched: () => switched++)],
        ),
      ),
    );

    setUp(() {
      contexts = _Contexts();
      meContext = MeContextCubit(
        repository: contexts,
        cache: InMemoryMeContextCache(),
      );
      switched = 0;
    });

    tearDown(() => meContext.close());

    testWidgets('is hidden for a person at one school', (tester) async {
      await cubit.syncWithContext(oneSchool);
      await pumpApp(tester, header());

      expect(find.byType(IconButton), findsNothing);
    });

    for (final Size size in [phone, desktop]) {
      testWidgets('switches school, refreshes the context and goes home '
          '(${size.width.toInt()} px)', (tester) async {
        await cubit.syncWithContext(twoSchools);
        await pumpApp(tester, header(), size: size);

        await tester.tap(find.byType(SchoolSwitcherButton));
        await tester.pumpAndSettle();
        expect(find.text('Choose a school'), findsOneWidget);
        expect(find.text('Northcliff High'), findsOneWidget);
        await tester.tap(find.text('Northcliff High'));
        await tester.pumpAndSettle();

        expect(store.schoolId, 1);
        expect(contexts.calls, 1);
        expect(switched, 1);
      });
    }

    testWidgets('picking the school already chosen changes nothing', (
      tester,
    ) async {
      await cubit.syncWithContext(twoSchools);
      await pumpApp(tester, header());

      await tester.tap(find.byType(SchoolSwitcherButton));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Riverside Primary'));
      await tester.pumpAndSettle();

      expect(contexts.calls, 0);
      expect(switched, 0);
    });

    testWidgets('has a name for screen readers and a large target', (
      tester,
    ) async {
      final SemanticsHandle semantics = tester.ensureSemantics();
      await cubit.syncWithContext(twoSchools);
      await pumpApp(tester, header());

      // A screen reader reads the button's tooltip as its name.
      expect(
        tester.getSemantics(find.byTooltip('Switch school, Riverside Primary')),
        isSemantics(
          tooltip: 'Switch school, Riverside Primary',
          isButton: true,
          hasTapAction: true,
        ),
      );
      await expectLater(tester, meetsGuideline(androidTapTargetGuideline));
      await expectLater(tester, meetsGuideline(labeledTapTargetGuideline));

      await tester.tap(find.byType(SchoolSwitcherButton));
      await tester.pumpAndSettle();
      expect(
        tester.getSemantics(
          find.ancestor(
            of: find.text('Riverside Primary').last,
            matching: find.byType(SchoolTile),
          ),
        ),
        isSemantics(isSelected: true, isButton: true, hasTapAction: true),
      );
      semantics.dispose();
    });
  });
}
