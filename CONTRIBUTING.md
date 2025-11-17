# Contributing to {{package|title}}

Thank you for considering contributing to {{package|title}}! We welcome contributions from the community and are excited to see what you can bring to the project.

## How to Contribute

1. **Fork the Repository**: Start by forking the repository to your GitHub account.

2. **Clone the Repository**: Clone your forked repository to your local machine.
    ```bash
    git clone https://github.com/{{namespace|slug,reverse}}.git
    ```

3. **Create a Branch**: Create a new branch for your feature or bugfix.
    ```bash
    git checkout -b feature/your-feature-name
    ```

4. **Install Dependencies**: Install the necessary dependencies using Composer.
    ```bash
    composer install
    ```

5. **Make Changes**: Make your changes to the codebase.

6. **Run Tests**: Ensure that all tests pass before submitting your changes.
    ```bash
    composer run test
    ```

7. **Commit Changes**: Commit your changes with a descriptive commit message.
    ```bash
    git add .
    git commit -m "Add feature: your feature description"
    ```

8. **Push Changes**: Push your changes to your forked repository.
    ```bash
    git push origin feature/your-feature-name
    ```

9. **Create a Pull Request**: Open a pull request to the main repository. Provide a clear description of your changes and any related issues.

## Style Guide

- Run the styling tool [Pint](https://laravel.com/docs/11.x/pint#main-content) before committing your changes.
- Write clear and concise commit messages.
- Document your code where necessary.

## Reporting Issues

If you find a bug or have a feature request, please open an issue on GitHub. Provide as much detail as possible to help us understand and address the issue.

Thank you for contributing!
