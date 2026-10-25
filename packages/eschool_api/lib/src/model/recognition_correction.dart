//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_correction.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionCorrection {
  /// Returns a new [RecognitionCorrection] instance.
  RecognitionCorrection({required this.operationId, required this.reason});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionCorrection &&
          other.operationId == operationId &&
          other.reason == reason;

  @override
  int get hashCode => operationId.hashCode + reason.hashCode;

  factory RecognitionCorrection.fromJson(Map<String, dynamic> json) =>
      _$RecognitionCorrectionFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionCorrectionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
