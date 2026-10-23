import 'package:equatable/equatable.dart';
import 'package:eschool_api/eschool_api.dart' as contract;
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/common/learner_summary.dart';

// GET /api/v2/guardian/approvals and POST /api/v2/guardian/approvals/{learner}
// Wire models come from the generated API contract; the classes below are UI view models.

/// The text a guardian agrees to when approving, in the version they saw.
class ApprovalConsent extends Equatable {
  const ApprovalConsent({required this.version, required this.text});

  factory ApprovalConsent.fromJson(Map<String, dynamic> json) {
    final value = contract.GuardianConsent.fromJson(json);
    return ApprovalConsent(version: value.version, text: value.text);
  }

  final String version;
  final String text;

  @override
  List<Object?> get props => [version, text];
}

/// One child's access, as their guardian sees it.
class GuardianApproval extends Equatable {
  const GuardianApproval({
    required this.learner,
    required this.status,
    required this.consent,
    this.requestedAt,
  });

  factory GuardianApproval.fromJson(Map<String, dynamic> json) {
    final value = contract.GuardianApproval.fromJson(json);
    return GuardianApproval(
      learner: LearnerSummary.fromJson(value.learner.toJson()),
      status: value.status.value,
      requestedAt: value.requestedAt,
      consent: ApprovalConsent(
        version: value.consent.version,
        text: value.consent.text,
      ),
    );
  }

  static const String pending = 'pending';
  static const String approved = 'approved';
  static const String withdrawn = 'withdrawn';

  final LearnerSummary learner;

  /// [pending], [approved] or [withdrawn].
  final String status;
  final DateTime? requestedAt;
  final ApprovalConsent consent;

  bool get isApproved => status == approved;

  @override
  List<Object?> get props => [learner, status, requestedAt, consent];
}

/// A guardian's children waiting for, or given, their approval.
abstract interface class ApprovalsRepository {
  /// Every child of the guardian with their approval; `null` when the
  /// server has no approvals yet (404).
  Future<List<GuardianApproval>?> fetch();

  /// Gives [learnerId] access, agreeing to [consentVersion].
  Future<void> approve(int learnerId, {required String consentVersion});

  /// Takes access away again: the learner is signed out.
  Future<void> withdraw(int learnerId, {required String consentVersion});
}

class ApiApprovalsRepository implements ApprovalsRepository {
  ApiApprovalsRepository(this._client, {String? baseUrl})
    : url = baseUrl ?? '${AppConfig.apiV2Url}guardian/approvals';

  final ApiClient _client;
  final String url;

  @override
  Future<List<GuardianApproval>?> fetch() async {
    final Map<String, dynamic>? result = await _client.send(
      method: 'GET',
      url: url,
      notFoundAsNull: true,
    );
    return result == null
        ? null
        : dataList(result).map(GuardianApproval.fromJson).toList();
  }

  @override
  Future<void> approve(int learnerId, {required String consentVersion}) =>
      _decide(learnerId, 'approve', consentVersion);

  @override
  Future<void> withdraw(int learnerId, {required String consentVersion}) =>
      _decide(learnerId, 'withdraw', consentVersion);

  Future<void> _decide(int learnerId, String decision, String version) async {
    await _client.send(
      method: 'POST',
      url: '$url/$learnerId',
      data: {'decision': decision, 'consent_version': version},
    );
  }
}

/// The key of the Approvals screen in `EndpointAvailability`.
const String approvalsFeature = 'approvals';

/// Whether the server has guardian approvals yet (for `AvailableOnly`).
Future<bool> approvalsDeployed(ApprovalsRepository repository) async =>
    await repository.fetch() != null;
