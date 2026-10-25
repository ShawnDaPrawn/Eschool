import 'package:eschool_api/eschool_api.dart';

/// Self-study feedback only. The server independently grades every synchronized answer.
class LocalPracticeAnswers {
  static String number(String input) {
    final value = input.trim();
    if (!RegExp(r'^-?(?:\d{1,12}(?:\.\d{1,6})?|\.\d{1,6})$').hasMatch(value)) {
      throw const FormatException(
        'Enter a decimal using a dot, without expressions or digit grouping.',
      );
    }
    final negative = value.startsWith('-');
    final parts = (negative ? value.substring(1) : value).split('.');
    var integer = parts.first.replaceFirst(RegExp(r'^0+'), '');
    if (integer.isEmpty) integer = '0';
    final fraction = parts.length == 1
        ? ''
        : parts[1].replaceFirst(RegExp(r'0+$'), '');
    final result = '$integer${fraction.isEmpty ? '' : '.$fraction'}';
    return '${negative && result != '0' ? '-' : ''}$result';
  }

  static bool correct(PracticeQuestion question, String answer) {
    switch (question.kind) {
      case PracticeQuestionKindEnum.number:
        return question.answers.contains(number(answer));
      case PracticeQuestionKindEnum.choice:
        final selected = answer.trim();
        if (!question.options.any((option) => option.id == selected)) {
          throw const FormatException('Choose an offered answer.');
        }
        return question.answers.contains(selected);
      default:
        throw const FormatException(
          'This question needs an updated practice reader.',
        );
    }
  }
}
