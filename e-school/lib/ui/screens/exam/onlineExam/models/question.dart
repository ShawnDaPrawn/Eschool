import 'package:eschool/ui/screens/exam/onlineExam/models/answerOption.dart';
import 'package:eschool/ui/screens/exam/onlineExam/models/answers.dart';

class Question {

  Question({
    this.questionType,
    this.answerOptions,
    this.correctAnswer,
    this.id,
    this.note,
    this.question,
    this.imageUrl,
    this.attempted = false,
    this.submittedAnswerId,
    this.marks,
  });
  final String? question;
  final int? id;
  final String? imageUrl;
  final List<Answers>? correctAnswer;
  final String? note;
  final List<int>? submittedAnswerId;
  final int? questionType;
  final List<AnswerOption>? answerOptions;
  final bool attempted;
  final String? marks;

  static Question fromJson(Map questionJson) {
    final List<AnswerOption> options = (questionJson['options'] as List)
        .map((e) => AnswerOption.fromJson(Map.from(e)))
        .toList();

    options.shuffle();

    final List<Answers> trueAns = (questionJson['answers'] as List)
        .map((e) => Answers.fromJson(Map.from(e)))
        .toList();

    return Question(
      id: questionJson['id'],
      imageUrl: questionJson['image'],
      correctAnswer: trueAns,
      question: questionJson['question'],
      note: questionJson['note'] ?? '',
      questionType: questionJson['question_type'] ?? 0,
      marks: questionJson['marks'].toString(),
      answerOptions: options,
      submittedAnswerId: [0],
    );
  }

  Question updateQuestionWithAnswer({required List<int> submittedAnswerId}) {
    return Question(
      marks: marks,
      submittedAnswerId: submittedAnswerId,
      answerOptions: answerOptions,
      attempted: submittedAnswerId.isNotEmpty,
      correctAnswer: correctAnswer,
      id: id,
      imageUrl: imageUrl,
      note: note,
      question: question,
      questionType: questionType,
    );
  }
}
