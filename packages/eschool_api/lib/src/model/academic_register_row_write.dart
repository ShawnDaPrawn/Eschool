//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_register_row_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRegisterRowWrite {
  /// Returns a new [AcademicRegisterRowWrite] instance.
  AcademicRegisterRowWrite({
    required this.learnerId,

    required this.state,

    required this.source_,

    required this.expectedRevision,

    this.qrToken,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  @JsonKey(name: r'source', required: true, includeIfNull: false)
  final String source_;

  // minimum: 0
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(name: r'qr_token', required: false, includeIfNull: false)
  final String? qrToken;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRegisterRowWrite &&
          other.learnerId == learnerId &&
          other.state == state &&
          other.source_ == source_ &&
          other.expectedRevision == expectedRevision &&
          other.qrToken == qrToken;

  @override
  int get hashCode =>
      learnerId.hashCode +
      state.hashCode +
      source_.hashCode +
      expectedRevision.hashCode +
      (qrToken == null ? 0 : qrToken.hashCode);

  factory AcademicRegisterRowWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicRegisterRowWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRegisterRowWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
