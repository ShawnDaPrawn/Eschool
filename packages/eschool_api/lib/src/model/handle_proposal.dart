//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'handle_proposal.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class HandleProposal {
  /// Returns a new [HandleProposal] instance.
  HandleProposal({
    required this.id,

    required this.handle,

    required this.status,

    required this.expiresAt,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'handle', required: true, includeIfNull: false)
  final String handle;

  @JsonKey(name: r'status', required: true, includeIfNull: false)
  final String status;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is HandleProposal &&
          other.id == id &&
          other.handle == handle &&
          other.status == status &&
          other.expiresAt == expiresAt;

  @override
  int get hashCode =>
      id.hashCode + handle.hashCode + status.hashCode + expiresAt.hashCode;

  factory HandleProposal.fromJson(Map<String, dynamic> json) =>
      _$HandleProposalFromJson(json);

  Map<String, dynamic> toJson() => _$HandleProposalToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
