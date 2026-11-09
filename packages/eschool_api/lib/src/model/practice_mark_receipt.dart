//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_mark_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeMarkReceipt {
  /// Returns a new [PracticeMarkReceipt] instance.
  PracticeMarkReceipt({
    required this.id,

    required this.state,

    required this.correct,

    required this.markedHow,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeMarkReceiptStateEnum.unknownDefaultOpenApi,
  )
  final PracticeMarkReceiptStateEnum state;

  @JsonKey(name: r'correct', required: true, includeIfNull: false)
  final bool correct;

  @JsonKey(
    name: r'marked_how',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeMarkReceiptMarkedHowEnum.unknownDefaultOpenApi,
  )
  final PracticeMarkReceiptMarkedHowEnum markedHow;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeMarkReceipt &&
          other.id == id &&
          other.state == state &&
          other.correct == correct &&
          other.markedHow == markedHow;

  @override
  int get hashCode =>
      id.hashCode + state.hashCode + correct.hashCode + markedHow.hashCode;

  factory PracticeMarkReceipt.fromJson(Map<String, dynamic> json) =>
      _$PracticeMarkReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeMarkReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeMarkReceiptStateEnum {
  @JsonValue(r'marked')
  marked(r'marked'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeMarkReceiptStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PracticeMarkReceiptMarkedHowEnum {
  @JsonValue(r'teacher')
  teacher(r'teacher'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeMarkReceiptMarkedHowEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
