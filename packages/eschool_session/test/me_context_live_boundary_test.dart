import 'dart:async';
import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'fixtures.dart';

class _Repository implements MeContextRepository {
  late MeContext current;
  @override
  Future<MeContext?> fetchContext() async => current;
}

void main() {
  testWidgets(
    'explicit module required and coalesced identical refresh replaces the live authorization generation',
    (tester) async {
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      final repository = _Repository()
        ..current = MeContext.fromJson(contextJson);
      final cubit = MeContextCubit(
        repository: repository,
        cache: InMemoryMeContextCache(),
      );
      final dio = Dio();
      var calls = 0;
      final pending = <Completer<void>>[];
      dio.interceptors.add(
        InterceptorsWrapper(
          onRequest: (request, handler) async {
            calls++;
            final gate = Completer<void>();
            pending.add(gate);
            await gate.future;
            handler.resolve(
              Response(
                requestOptions: request,
                statusCode: 503,
                data: {
                  'error': true,
                  'code': 'server_error',
                  'message': 'Unavailable',
                },
              ),
            );
          },
        ),
      );
      final api = ApiClient(
        authToken: () => 'token',
        onUnauthorized: () {},
        createDio: () => dio,
      );
      LiveSession? active;
      await cubit.load();
      await tester.pumpWidget(
        BlocProvider.value(
          value: cubit,
          child: MeContextLiveBoundary(
            client: api,
            child: Builder(
              builder: (context) {
                active = LiveUpdatesScope.sessionOf(context);
                return const SizedBox();
              },
            ),
          ),
        ),
      );
      await tester.pump();
      expect(active, isNull);
      expect(calls, 0);
      repository.current = MeContext.fromJson({
        ...contextJson,
        'modules': [
          {'key': 'live_updates', 'name': 'Live updates', 'read_only': false},
        ],
      });
      await cubit.load();
      await tester.pump();
      await tester.pump();
      expect(active, isNotNull);
      expect(active!.active, isTrue);
      await tester.pump(const Duration(milliseconds: 1));
      expect(calls, 1);
      final previous = active!;
      await cubit
          .load(); // Identical object and payload; loading/loaded before next frame.
      await tester.pump();
      await tester.pump();
      expect(identical(previous, active), isFalse);
      expect(previous.active, isFalse);
      await tester.pump(const Duration(milliseconds: 1));
      expect(calls, 2);
      await cubit.clear();
      await tester.pump();
      await tester.pump();
      expect(active, isNull);
      for (final gate in pending) {
        gate.complete();
      }
      await tester.pumpWidget(const SizedBox());
      await tester.pump();
      await cubit.close();
    },
  );
}
