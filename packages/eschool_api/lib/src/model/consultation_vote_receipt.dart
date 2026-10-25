//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'consultation_vote_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationVoteReceipt {
  /// Returns a new [ConsultationVoteReceipt] instance.
  ConsultationVoteReceipt({required this.participated});

  @JsonKey(name: r'participated', required: true, includeIfNull: false)
  final bool participated;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationVoteReceipt && other.participated == participated;

  @override
  int get hashCode => participated.hashCode;

  factory ConsultationVoteReceipt.fromJson(Map<String, dynamic> json) =>
      _$ConsultationVoteReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationVoteReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
