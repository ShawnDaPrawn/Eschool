//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'transfer_access.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TransferAccess {
  /// Returns a new [TransferAccess] instance.
  TransferAccess({
    required this.staff,

    required this.guardian,

    required this.canWrite,

    required this.consentVersion,

    required this.today,
  });

  @JsonKey(name: r'staff', required: true, includeIfNull: false)
  final bool staff;

  @JsonKey(name: r'guardian', required: true, includeIfNull: false)
  final bool guardian;

  @JsonKey(name: r'can_write', required: true, includeIfNull: false)
  final bool canWrite;

  @JsonKey(name: r'consent_version', required: true, includeIfNull: false)
  final String consentVersion;

  @JsonKey(name: r'today', required: true, includeIfNull: false)
  final String today;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TransferAccess &&
          other.staff == staff &&
          other.guardian == guardian &&
          other.canWrite == canWrite &&
          other.consentVersion == consentVersion &&
          other.today == today;

  @override
  int get hashCode =>
      staff.hashCode +
      guardian.hashCode +
      canWrite.hashCode +
      consentVersion.hashCode +
      today.hashCode;

  factory TransferAccess.fromJson(Map<String, dynamic> json) =>
      _$TransferAccessFromJson(json);

  Map<String, dynamic> toJson() => _$TransferAccessToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
