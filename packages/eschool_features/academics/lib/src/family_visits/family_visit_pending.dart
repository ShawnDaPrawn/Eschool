import '../private/private_pending_intent.dart';
import 'family_visit_repository.dart';

/// Only an already-submitted exact request can enter this short-lived memory copy.
class FamilyVisitPending {
  FamilyVisitPending({
    required FamilyVisitRepository repository,
    required FamilyVisitMutation intent,
    this.learnerId,
    DateTime Function()? clock,
  }) : action = intent.action,
       operation = intent.body['operation_id'] as String,
       _sealed = PrivatePendingIntent(
         school: repository.schoolId,
         reader: repository.accountId,
         body: intent.toJson(),
         clock: clock,
       );
  final FamilyVisitAction action;
  final String operation;
  final int? learnerId;
  final PrivatePendingIntent _sealed;
  bool get expired => _sealed.expired;
  FamilyVisitMutation open(
    FamilyVisitRepository repository, {
    required bool authorized,
  }) => FamilyVisitMutation.fromJson(
    _sealed.open(
      school: repository.schoolId,
      reader: repository.accountId,
      authorized: authorized && repository.isActive,
    ),
  );
  void close() => _sealed.close();
}
