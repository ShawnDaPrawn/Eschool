import '../private/private_pending_intent.dart';
import 'peer_repository.dart';

class PeerPending {
  PeerPending(
    PeerRepository repository,
    PeerIntent intent, {
    DateTime Function()? clock,
  }) : _sealed = PrivatePendingIntent(
         school: repository.schoolId,
         reader: repository.accountId,
         body: intent.toJson(),
         clock: clock,
       );
  final PrivatePendingIntent _sealed;
  bool get expired => _sealed.expired;
  PeerIntent open(PeerRepository repository, {required bool authorized}) =>
      PeerIntent.fromJson(
        _sealed.open(
          school: repository.schoolId,
          reader: repository.accountId,
          authorized:
              authorized &&
              repository.isActive &&
              repository.persona == 'staff',
        ),
      );
  void close() => _sealed.close();
}
