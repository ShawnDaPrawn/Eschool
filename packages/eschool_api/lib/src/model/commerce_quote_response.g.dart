// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'commerce_quote_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommerceQuoteResponse _$CommerceQuoteResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CommerceQuoteResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = CommerceQuoteResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => CommerceQuote.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$CommerceQuoteResponseToJson(
  CommerceQuoteResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
