import 'package:equatable/equatable.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/common/learner_summary.dart';

// GET /api/v2/guardian/invitations/{token} and POST .../{token}/accept
// (Phase 1 contract). Hand-written until openapi.yaml has them; then use the
// generated models and client.

/// A guardian's invitation from a school, opened from its link.
class GuardianInvitation extends Equatable {
  const GuardianInvitation({
    required this.email,
    required this.learners,
    required this.needsPassword,
    this.school,
    this.expiresAt,
  });

  factory GuardianInvitation.fromJson(Map<String, dynamic> json) =>
      GuardianInvitation(
        school: readSchool(json['school']),
        email: '${json['email'] ?? ''}',
        learners: [
          if (json['learners'] is List)
            for (final Object? learner in json['learners'] as List)
              if (learner is Map)
                LearnerSummary.fromJson(Map<String, dynamic>.from(learner)),
        ],
        expiresAt: readDate(json['expires_at']),
        needsPassword: json['needs_password'] == true,
      );

  final SchoolSummary? school;

  /// The address the invitation was sent to: the guardian's sign-in.
  final String email;

  /// The children the guardian is invited for (`{id, name}`).
  final List<LearnerSummary> learners;
  final DateTime? expiresAt;

  /// A new guardian sets a password when accepting; one who already has an
  /// account does not.
  final bool needsPassword;

  @override
  List<Object?> get props => [
    school?.id,
    email,
    learners,
    expiresAt,
    needsPassword,
  ];
}

abstract interface class InvitationRepository {
  /// The invitation behind [token]; `null` when it does not exist, has
  /// expired or was used (or the server has no invitations yet).
  Future<GuardianInvitation?> fetch(String token);

  /// Accepts the invitation, setting [password] when the invitation needs
  /// one. Answers like a sign-in: `{data: <the parent>, token}`.
  Future<Map<String, dynamic>> accept(String token, {String? password});
}

class ApiInvitationRepository implements InvitationRepository {
  ApiInvitationRepository(this._client, {this.deviceFields, String? baseUrl})
    : url = baseUrl ?? '${AppConfig.apiV2Url}guardian/invitations';

  final ApiClient _client;
  final String url;

  /// What a sign-in tells the server about this device (the FCM token),
  /// sent with the acceptance as with any sign-in.
  final Future<Map<String, dynamic>> Function()? deviceFields;

  @override
  Future<GuardianInvitation?> fetch(String token) async {
    final Map<String, dynamic>? result = await _client.send(
      method: 'GET',
      url: '$url/${Uri.encodeComponent(token)}',
      useAuthToken: false,
      privateRequest: true,
      notFoundAsNull: true,
    );
    return result == null ? null : GuardianInvitation.fromJson(dataMap(result));
  }

  @override
  Future<Map<String, dynamic>> accept(String token, {String? password}) async {
    final Map<String, dynamic>? result = await _client.send(
      method: 'POST',
      url: '$url/${Uri.encodeComponent(token)}/accept',
      useAuthToken: false,
      privateRequest: true,
      data: {'password': ?password, ...?await deviceFields?.call()},
    );
    return result ?? const {};
  }
}
