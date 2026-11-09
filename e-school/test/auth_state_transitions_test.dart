import 'dart:async';

import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/cubits/resetPasswordRequestCubit.dart';
import 'package:eschool/cubits/signInCubit.dart';
import 'package:eschool/data/repositories/authRepository.dart';
import 'package:flutter_test/flutter_test.dart';

class _DeferredReset extends AuthRepository {
  final answer = Completer<void>();

  @override
  Future<void> resetPasswordRequest({
    required String grNumber,
    required DateTime dob,
  }) => answer.future;
}

void main() {
  test('authentication phases with empty properties stay distinct', () {
    final phases = [
      AuthInitial(),
      Unauthenticated(),
      SignInInitial(),
      SignInInProgress(),
      RequestResetPasswordInitial(),
      RequestResetPasswordInProgress(),
      RequestResetPasswordSuccess(),
    ];

    // BLoC suppresses equal states. Losing the type distinction would prevent
    // loading, signed-out, and completed authentication screens from updating.
    expect(phases.toSet(), hasLength(phases.length));
    for (var i = 0; i < phases.length; i++) {
      for (var j = 0; j < phases.length; j++) {
        expect(phases[i] == phases[j], i == j);
      }
    }
    expect(SignInInitial(), SignInInitial());
  });

  test(
    'reset request emits loading then success with the upgraded equality',
    () async {
      final repository = _DeferredReset();
      final cubit = RequestResetPasswordCubit(repository);
      addTearDown(cubit.close);
      final emitted = <RequestResetPasswordState>[];
      final subscription = cubit.stream.listen(emitted.add);
      addTearDown(subscription.cancel);

      final request = cubit.requestResetPassword(
        grNumber: 'test-learner',
        dob: DateTime(2015),
      );
      await Future<void>.delayed(Duration.zero);
      expect(cubit.state, isA<RequestResetPasswordInProgress>());

      repository.answer.complete();
      await request;
      await Future<void>.delayed(Duration.zero);
      expect(emitted, [
        isA<RequestResetPasswordInProgress>(),
        isA<RequestResetPasswordSuccess>(),
      ]);
    },
  );
}
