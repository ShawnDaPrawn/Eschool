import 'dart:async';

import 'package:eschool_session/eschool_session.dart';
import 'package:flutter_test/flutter_test.dart';

final DeepLinkParser parser = DeepLinkParser(
  routes: [
    const DeepLinkRoute('/notices', '/noticeBoard'),
    const DeepLinkRoute('/noticeBoard', '/noticeBoard'),
    DeepLinkRoute(
      '/fees/:childId',
      '/feesDetails',
      arguments: (values, _) => int.parse(values['childId']!),
    ),
    DeepLinkRoute(
      '/calendar',
      '/calendar',
      arguments: (_, query) => query['month'],
    ),
  ],
);

DeepLinkTarget? parse(String link) => parser.parse(Uri.parse(link));

class FakeDeepLinkSource implements DeepLinkSource {
  FakeDeepLinkSource(this.initial);

  final Uri? initial;
  final StreamController<Uri> controller = StreamController<Uri>();

  @override
  Future<Uri?> initialLink() async => initial;

  @override
  Stream<Uri> get links => controller.stream;
}

void main() {
  group('DeepLinkParser', () {
    test('maps a portal address to the route of the same screen', () {
      expect(
        parse('https://app.navigateed.co.za/notices'),
        const DeepLinkTarget('/noticeBoard'),
      );
      expect(
        parse('https://teach.navigateed.co.za/noticeBoard/'),
        const DeepLinkTarget('/noticeBoard'),
      );
    });

    test('reads values from the path and the query', () {
      expect(
        parse('https://app.navigateed.co.za/fees/42'),
        const DeepLinkTarget('/feesDetails', arguments: 42),
      );
      expect(
        parse('https://staging-app.navigateed.co.za/calendar?month=5'),
        const DeepLinkTarget('/calendar', arguments: '5'),
      );
    });

    test('understands Flutter web hash addresses and the app scheme', () {
      expect(
        parse('https://app.navigateed.co.za/#/notices'),
        const DeepLinkTarget('/noticeBoard'),
      );
      expect(
        parse('navigateed://fees/7'),
        const DeepLinkTarget('/feesDetails', arguments: 7),
      );
    });

    test('ignores links that are not ours or name no screen', () {
      expect(parse('https://evil.example/notices'), isNull);
      expect(parse('ftp://app.navigateed.co.za/notices'), isNull);
      expect(parse('https://app.navigateed.co.za/unknown'), isNull);
      expect(parse('https://app.navigateed.co.za/notices/extra'), isNull);
      expect(parse('https://app.navigateed.co.za/'), isNull);
    });

    test('a value the route cannot use makes the link invalid', () {
      expect(parse('https://app.navigateed.co.za/fees/abc'), isNull);
    });

    test('a web build can add its own origin', () {
      final local = DeepLinkParser(
        routes: parser.routes,
        hosts: {...DeepLinkParser.defaultHosts, 'localhost'},
      );

      expect(
        local.parse(Uri.parse('http://localhost:8080/notices')),
        const DeepLinkTarget('/noticeBoard'),
      );
    });
  });

  group('DeepLinkCoordinator', () {
    test('keeps the link that opened the app until it is taken', () async {
      final coordinator = DeepLinkCoordinator(
        parser: parser,
        source: FakeDeepLinkSource(
          Uri.parse('https://app.navigateed.co.za/notices'),
        ),
      );

      await coordinator.start();

      expect(coordinator.takePending(), const DeepLinkTarget('/noticeBoard'));
      expect(coordinator.takePending(), isNull);
      await coordinator.dispose();
    });

    test('hands later links to whoever listens, else keeps the last', () async {
      final source = FakeDeepLinkSource(null);
      final coordinator = DeepLinkCoordinator(parser: parser, source: source);
      await coordinator.start();

      source.controller.add(Uri.parse('navigateed://fees/3'));
      await Future<void>.delayed(Duration.zero);
      expect(
        coordinator.takePending(),
        const DeepLinkTarget('/feesDetails', arguments: 3),
      );

      final received = <DeepLinkTarget>[];
      final subscription = coordinator.targets.listen(received.add);
      source.controller.add(Uri.parse('https://app.navigateed.co.za/notices'));
      source.controller.add(Uri.parse('https://other.example/notices'));
      await Future<void>.delayed(Duration.zero);

      expect(received, [const DeepLinkTarget('/noticeBoard')]);
      expect(coordinator.takePending(), isNull);

      // A link the app cannot open yet waits for the next takePending.
      coordinator.keepPending(received.single);
      expect(coordinator.takePending(), const DeepLinkTarget('/noticeBoard'));
      await subscription.cancel();
      await coordinator.dispose();
    });

    test('a link that opens before sign-in can be taken on its own', () async {
      final coordinator = DeepLinkCoordinator(
        parser: parser,
        source: FakeDeepLinkSource(
          Uri.parse('https://app.navigateed.co.za/notices'),
        ),
      );
      await coordinator.start();

      expect(
        coordinator.takePendingWhere((t) => t.routeName == '/calendar'),
        isNull,
      );
      expect(
        coordinator.takePendingWhere((t) => t.routeName == '/noticeBoard'),
        const DeepLinkTarget('/noticeBoard'),
      );
      expect(coordinator.takePending(), isNull);
      await coordinator.dispose();
    });
  });

  group('a push link', () {
    test('an app path maps like a portal address', () {
      expect(
        parser.parsePath('/notices'),
        const DeepLinkTarget('/noticeBoard'),
      );
      expect(
        parser.parsePath('/fees/7?x=1'),
        const DeepLinkTarget('/feesDetails', arguments: 7),
      );
      expect(parser.parsePath('/nowhere'), isNull);
      expect(parser.parsePath(''), isNull);
      expect(parser.parsePath('https://evil.example/notices'), isNull);
    });

    test('carries the school it belongs to', () {
      final PushLink link = PushLink.fromData({
        'type': 'comms.notice',
        'link': '/notices',
        'school_id': '2',
      }, parser: parser)!;

      expect(link.target, const DeepLinkTarget('/noticeBoard'));
      expect(link.schoolId, 2);
      expect(
        PushLink.fromData({'link': '/notices'}, parser: parser)!.schoolId,
        isNull,
      );
      expect(PushLink.fromData({'type': 'chat'}, parser: parser), isNull);
      expect(PushLink.fromData({'link': '/nowhere'}, parser: parser), isNull);
      expect(
        PushLink.fromData({
          'link': 'https://app.navigateed.co.za/notices',
        }, parser: parser)!.target,
        const DeepLinkTarget('/noticeBoard'),
      );
    });

    test(
      'opens after switching to its school and fetching its context',
      () async {
        final InMemorySelectionStore store = InMemorySelectionStore();
        final SchoolSelectionCubit schools = SchoolSelectionCubit(store: store);
        await schools.syncWithContext(
          MeContext.fromJson({
            'schools': [
              for (final int id in [1, 2])
                {
                  'school': {
                    'id': id,
                    'name': 'School $id',
                    'short_name': null,
                    'crest_url': null,
                  },
                  'roles': ['Parent'],
                },
            ],
          }),
        );
        final _CountingContext contexts = _CountingContext();
        final MeContextCubit meContext = MeContextCubit(
          repository: contexts,
          cache: InMemoryMeContextCache(),
        );
        final List<String> steps = [];

        await openPushLink(
          const PushLink(target: DeepLinkTarget('/noticeBoard'), schoolId: 2),
          schools: schools,
          meContext: meContext,
          open: (target) =>
              steps.add('open ${target.routeName} at ${store.schoolId}'),
        );

        expect(contexts.calls, 1);
        expect(steps, ['open /noticeBoard at 2']);
        await schools.close();
        await meContext.close();
      },
    );
  });
}

class _CountingContext implements MeContextRepository {
  int calls = 0;

  @override
  Future<MeContext?> fetchContext() async {
    calls++;
    return MeContext.fromJson(const {});
  }
}
