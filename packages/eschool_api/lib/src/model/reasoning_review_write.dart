//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reasoning_review_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReasoningReviewWrite {
  /// Returns a new [ReasoningReviewWrite] instance.
  ReasoningReviewWrite({
    required this.operationId,

    required this.text,

    required this.source_,

    required this.fromSide,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  /// A speech transcript or written argument. Names are removed on the server before anything is sent.
  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @JsonKey(
    name: r'source',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ReasoningReviewWriteSource_Enum.unknownDefaultOpenApi,
  )
  final ReasoningReviewWriteSource_Enum source_;

  @JsonKey(
    name: r'from_side',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ReasoningReviewWriteFromSideEnum.unknownDefaultOpenApi,
  )
  final ReasoningReviewWriteFromSideEnum fromSide;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReasoningReviewWrite &&
          other.operationId == operationId &&
          other.text == text &&
          other.source_ == source_ &&
          other.fromSide == fromSide;

  @override
  int get hashCode =>
      operationId.hashCode +
      text.hashCode +
      source_.hashCode +
      fromSide.hashCode;

  factory ReasoningReviewWrite.fromJson(Map<String, dynamic> json) =>
      _$ReasoningReviewWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ReasoningReviewWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ReasoningReviewWriteSource_Enum {
  @JsonValue(r'speech')
  speech(r'speech'),
  @JsonValue(r'written')
  written(r'written'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ReasoningReviewWriteSource_Enum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum ReasoningReviewWriteFromSideEnum {
  @JsonValue(r'ours')
  ours(r'ours'),
  @JsonValue(r'theirs')
  theirs(r'theirs'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ReasoningReviewWriteFromSideEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
