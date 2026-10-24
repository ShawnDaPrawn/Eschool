//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_register_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRegisterLearner {
  /// Returns a new [AcademicRegisterLearner] instance.
  AcademicRegisterLearner({
    required this.learnerId,

    required this.name,

    required this.state,

    required this.revision,

    required this.source_,

    required this.amendedAt,

    required this.qrCode,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'state', required: true, includeIfNull: false)
  final String state;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'source', required: true, includeIfNull: true)
  final String? source_;

  @JsonKey(name: r'amended_at', required: true, includeIfNull: true)
  final String? amendedAt;

  @JsonKey(name: r'qr_code', required: true, includeIfNull: false)
  final String qrCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRegisterLearner &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.state == state &&
          other.revision == revision &&
          other.source_ == source_ &&
          other.amendedAt == amendedAt &&
          other.qrCode == qrCode;

  @override
  int get hashCode =>
      learnerId.hashCode +
      name.hashCode +
      state.hashCode +
      revision.hashCode +
      (source_ == null ? 0 : source_.hashCode) +
      (amendedAt == null ? 0 : amendedAt.hashCode) +
      qrCode.hashCode;

  factory AcademicRegisterLearner.fromJson(Map<String, dynamic> json) =>
      _$AcademicRegisterLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRegisterLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
